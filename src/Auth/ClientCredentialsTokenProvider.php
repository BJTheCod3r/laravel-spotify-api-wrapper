<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Auth;

use BjTheCod3r\Spotify\Config\SpotifyConfig;
use BjTheCod3r\Spotify\Contracts\TokenProvider;
use BjTheCod3r\Spotify\Exceptions\AuthenticationException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class ClientCredentialsTokenProvider implements TokenProvider
{
    public function __construct(
        protected SpotifyConfig $config,
        protected CacheRepository $cache,
    ) {
    }

    public function token(): Token
    {
        $cached = $this->cache->get($this->config->cacheKey);

        if (is_array($cached)) {
            $token = Token::fromArray($cached);

            if (! $token->isExpired($this->config->cacheTtlBuffer)) {
                return $token;
            }
        }

        return $this->refresh();
    }

    public function forget(): void
    {
        $this->cache->forget($this->config->cacheKey);
    }

    protected function refresh(): Token
    {
        if ($this->config->clientId === '' || $this->config->clientSecret === '') {
            throw AuthenticationException::missingCredentials();
        }

        try {
            $response = Http::asForm()
                ->withHeaders([
                    'Authorization' => 'Basic '.base64_encode(
                        $this->config->clientId.':'.$this->config->clientSecret
                    ),
                ])
                ->timeout($this->config->httpTimeout)
                ->post($this->config->tokenUrl(), [
                    'grant_type' => 'client_credentials',
                ]);
        } catch (ConnectionException $e) {
            throw AuthenticationException::connectionFailed($e);
        } catch (Throwable $e) {
            throw AuthenticationException::unexpected($e);
        }

        if ($response->failed()) {
            throw AuthenticationException::fromResponse(
                status: $response->status(),
                body: (array) $response->json(),
            );
        }

        $payload = (array) $response->json();
        $expiresIn = (int) ($payload['expires_in'] ?? 3600);

        $token = new Token(
            accessToken: (string) ($payload['access_token'] ?? ''),
            tokenType: (string) ($payload['token_type'] ?? 'Bearer'),
            expiresAt: Carbon::now()->addSeconds($expiresIn),
        );

        $ttl = max(1, $expiresIn - $this->config->cacheTtlBuffer);
        $this->cache->put($this->config->cacheKey, $token->toArray(), $ttl);

        return $token;
    }
}
