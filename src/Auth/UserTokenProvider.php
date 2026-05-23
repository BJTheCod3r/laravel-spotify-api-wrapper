<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Auth;

use BjTheCod3r\Spotify\Config\SpotifyConfig;
use BjTheCod3r\Spotify\Contracts\TokenProvider;
use BjTheCod3r\Spotify\Contracts\UserTokenRepository;
use BjTheCod3r\Spotify\Events\SpotifyDisconnected;
use BjTheCod3r\Spotify\Events\SpotifyTokenRefreshed;
use BjTheCod3r\Spotify\Exceptions\AuthenticationException;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class UserTokenProvider implements TokenProvider
{
    private ?UserTokenSet $cached = null;

    private bool $forceRefresh = false;

    public function __construct(
        protected SpotifyConfig $config,
        protected UserTokenRepository $repository,
        protected CacheFactory $cacheFactory,
        protected Dispatcher $events,
        protected int|string $userId,
    ) {
    }

    public function token(): Token
    {
        $set = $this->cached ?? $this->repository->find($this->userId);

        if ($set === null) {
            throw AuthenticationException::notConnected($this->userId);
        }

        $mustRefresh = $this->forceRefresh || $set->isExpired($this->config->cacheTtlBuffer);

        if (! $mustRefresh) {
            $this->cached = $set;

            return $this->asAppToken($set);
        }

        $refreshed = $this->refreshUnderLock($set);
        $this->cached = $refreshed;
        $this->forceRefresh = false;

        return $this->asAppToken($refreshed);
    }

    /**
     * Force the next `token()` call to refresh via Spotify, even if the
     * stored access token hasn't yet expired. Triggered by SpotifyClient's
     * 401 retry path — a still-fresh-by-clock token can be revoked
     * server-side, and a naive retry would loop with the same bad token.
     *
     * Persistent state (refresh token) is left intact so the user doesn't
     * have to reconnect.
     */
    public function forget(): void
    {
        $this->cached = null;
        $this->forceRefresh = true;
    }

    public function userId(): int|string
    {
        return $this->userId;
    }

    private function asAppToken(UserTokenSet $set): Token
    {
        return new Token(
            accessToken: $set->accessToken,
            tokenType: $set->tokenType,
            expiresAt: $set->expiresAt,
        );
    }

    private function refreshUnderLock(UserTokenSet $stale): UserTokenSet
    {
        $oauth = $this->config->oauth;

        if (! $oauth->refreshLock) {
            return $this->doRefresh($stale);
        }

        $store = $this->config->cacheStore !== null
            ? $this->cacheFactory->store($this->config->cacheStore)
            : $this->cacheFactory->store();

        $lock = $store->lock($this->lockKey(), $oauth->refreshLockTtl);

        return $lock->block($oauth->refreshLockWait, function () use ($stale): UserTokenSet {
            $current = $this->repository->find($this->userId);

            // If a sibling worker already rotated to a new access token,
            // use what they stored rather than re-spending the refresh
            // token. Detected by the access-token value changing — this
            // covers both clock-expired refreshes and 401-driven ones.
            if ($current !== null && $current->accessToken !== $stale->accessToken) {
                return $current;
            }

            return $this->doRefresh($current ?? $stale);
        });
    }

    private function doRefresh(UserTokenSet $stale): UserTokenSet
    {
        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout($this->config->httpTimeout)
                ->post($this->config->tokenUrl(), [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $stale->refreshToken,
                    'client_id' => $this->config->clientId,
                ]);
        } catch (ConnectionException $e) {
            throw AuthenticationException::connectionFailed($e);
        } catch (Throwable $e) {
            throw AuthenticationException::unexpected($e);
        }

        if ($response->failed()) {
            $body = (array) $response->json();
            $error = (string) ($body['error'] ?? '');

            if ($error === 'invalid_grant') {
                $this->repository->forget($this->userId);
                $this->cached = null;
                $this->events->dispatch(new SpotifyDisconnected(
                    userId: $this->userId,
                    reason: SpotifyDisconnected::REASON_INVALID_GRANT,
                ));

                throw AuthenticationException::invalidGrant();
            }

            throw AuthenticationException::fromResponse(
                status: $response->status(),
                body: $body,
            );
        }

        $fresh = $stale->refreshedWith((array) $response->json());
        $this->repository->store($this->userId, $fresh);

        $this->events->dispatch(new SpotifyTokenRefreshed(
            userId: $this->userId,
            expiresAt: $fresh->expiresAt,
        ));

        return $fresh;
    }

    private function lockKey(): string
    {
        return 'spotify.oauth.refresh.'.$this->userId;
    }
}
