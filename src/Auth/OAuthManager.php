<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Auth;

use BjTheCod3r\Spotify\Config\SpotifyConfig;
use BjTheCod3r\Spotify\Contracts\UserTokenRepository;
use BjTheCod3r\Spotify\Events\SpotifyConnected;
use BjTheCod3r\Spotify\Events\SpotifyConnectFailed;
use BjTheCod3r\Spotify\Events\SpotifyDisconnected;
use BjTheCod3r\Spotify\Exceptions\AuthenticationException;
use BjTheCod3r\Spotify\Http\SpotifyClient;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Coordinates the user-OAuth lifecycle: redirect → callback → token storage
 * → per-user HTTP client construction → disconnect. Stateless aside from
 * the session/repository it talks to.
 */
class OAuthManager
{
    public function __construct(
        protected SpotifyConfig $config,
        protected UserTokenRepository $repository,
        protected AuthFactory $auth,
        protected CacheFactory $cache,
        protected Dispatcher $events,
    ) {
    }

    /**
     * Build the authorize URL, stash PKCE state in the session, return a
     * redirect response pointing at Spotify's consent screen.
     *
     * @param list<string>|null $scopes Scopes to request. Null → defaults.
     * @param bool              $replaceScopes Replace rather than merge with defaults.
     */
    public function redirect(Session $session, ?array $scopes = null, bool $replaceScopes = false): RedirectResponse
    {
        $resolved = $this->resolveScopes($scopes, $replaceScopes);
        $pair = Pkce::generate();
        $state = Pkce::state();

        $this->session($session)->put($state, $pair['verifier'], $resolved);

        $url = (new AuthorizationUrlBuilder($this->config))
            ->build($state, $pair['challenge'], $resolved);

        return new RedirectResponse($url);
    }

    /**
     * Handle the OAuth callback: validate state, exchange the code, capture
     * the Spotify user id, persist, fire SpotifyConnected.
     *
     * Failure modes dispatch SpotifyConnectFailed and throw an exception so
     * the host app can decide how to render the error.
     */
    public function handleCallback(Request $request): UserTokenSet
    {
        $session = $this->session($request->session());
        $userId = $this->currentUserId();

        try {
            $error = (string) $request->query('error', '');

            if ($error !== '') {
                $reason = $error === 'access_denied'
                    ? SpotifyConnectFailed::REASON_USER_DENIED
                    : SpotifyConnectFailed::REASON_AUTHORIZE_ERROR;

                throw $this->fail(
                    $session,
                    $userId,
                    $reason,
                    $error,
                    $reason === SpotifyConnectFailed::REASON_USER_DENIED
                        ? AuthenticationException::userDenied($error)
                        : AuthenticationException::authorizeError($error),
                );
            }

            $expected = $session->state();
            $received = (string) $request->query('state', '');

            if ($expected === null || $received === '' || ! hash_equals($expected, $received)) {
                throw $this->fail(
                    $session,
                    $userId,
                    SpotifyConnectFailed::REASON_STATE_MISMATCH,
                    null,
                    AuthenticationException::stateMismatch(),
                );
            }

            $verifier = $session->verifier();
            $code = (string) $request->query('code', '');

            if ($verifier === null || $code === '') {
                throw $this->fail(
                    $session,
                    $userId,
                    SpotifyConnectFailed::REASON_EXCHANGE_FAILED,
                    'Missing code or PKCE verifier.',
                    AuthenticationException::stateMismatch(),
                );
            }

            try {
                $tokens = (new AuthorizationCodeExchanger($this->config))
                    ->exchange($code, $verifier);
            } catch (AuthenticationException $e) {
                throw $this->fail(
                    $session,
                    $userId,
                    SpotifyConnectFailed::REASON_EXCHANGE_FAILED,
                    $e->getMessage(),
                    $e,
                );
            }

            $tokens = $this->captureSpotifyUserId($tokens);
            $this->repository->store($userId, $tokens);

            $this->events->dispatch(new SpotifyConnected(
                userId: $userId,
                spotifyUserId: $tokens->spotifyUserId,
                scopes: $tokens->scopes,
            ));

            return $tokens;
        } finally {
            $session->consume();
        }
    }

    /**
     * Consume the PKCE session, flash the failure for the after-connect
     * destination, dispatch SpotifyConnectFailed, and return the exception
     * to throw. Returning rather than throwing keeps the call-site explicit
     * about control flow.
     */
    private function fail(
        AuthorizationSession $session,
        int|string|null $userId,
        string $reason,
        ?string $description,
        AuthenticationException $exception,
    ): AuthenticationException {
        $session->flashError($reason, $description);
        $session->consume();

        $this->events->dispatch(new SpotifyConnectFailed(
            userId: $userId,
            reason: $reason,
            description: $description,
        ));

        return $exception;
    }

    public function disconnect(int|string|null $userId = null): void
    {
        $resolved = $userId ?? $this->currentUserId();

        $this->repository->forget($resolved);
        $this->events->dispatch(new SpotifyDisconnected(
            userId: $resolved,
            reason: SpotifyDisconnected::REASON_USER,
        ));
    }

    public function providerFor(int|string $userId): UserTokenProvider
    {
        return new UserTokenProvider(
            config: $this->config,
            repository: $this->repository,
            cacheFactory: $this->cache,
            events: $this->events,
            userId: $userId,
        );
    }

    public function clientFor(int|string $userId): SpotifyClient
    {
        return new SpotifyClient(
            tokenProvider: $this->providerFor($userId),
            config: $this->config,
        );
    }

    public function currentUserId(): int|string
    {
        $id = $this->auth->guard($this->config->oauth->guard)->id();

        if ($id === null) {
            throw AuthenticationException::noAuthenticatedUser();
        }

        return $id;
    }

    /**
     * @param list<string>|null $scopes
     *
     * @return list<string>
     */
    protected function resolveScopes(?array $scopes, bool $replace): array
    {
        if ($scopes === null || $scopes === []) {
            return $this->config->oauth->defaultScopes;
        }

        $clean = array_values(array_filter(array_map('strval', $scopes), static fn (string $s): bool => $s !== ''));

        if ($replace) {
            return array_values(array_unique($clean));
        }

        return array_values(array_unique(array_merge($this->config->oauth->defaultScopes, $clean)));
    }

    protected function session(Session $session): AuthorizationSession
    {
        return new AuthorizationSession($session, $this->config->oauth->sessionKey);
    }

    /**
     * Hit /me with the freshly issued access token to capture the Spotify
     * user id. Failures here aren't fatal — the connection still works,
     * just without the linked id.
     */
    protected function captureSpotifyUserId(UserTokenSet $tokens): UserTokenSet
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $tokens->authorizationHeader(),
                'Accept' => 'application/json',
            ])
                ->timeout($this->config->httpTimeout)
                ->get($this->config->apiUrl.'/me');
        } catch (ConnectionException | Throwable) {
            return $tokens;
        }

        if ($response->failed()) {
            return $tokens;
        }

        $body = (array) $response->json();
        $id = isset($body['id']) && $body['id'] !== '' ? (string) $body['id'] : null;

        return $id === null ? $tokens : $tokens->withSpotifyUserId($id);
    }
}
