<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Auth;

use BjTheCod3r\Spotify\Config\SpotifyConfig;
use BjTheCod3r\Spotify\Exceptions\AuthenticationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

final class AuthorizationCodeExchanger
{
    public function __construct(private readonly SpotifyConfig $config)
    {
    }

    public function exchange(string $code, string $codeVerifier): UserTokenSet
    {
        if ($this->config->clientId === '') {
            throw AuthenticationException::missingCredentials();
        }

        $redirectUri = $this->config->oauth->redirectUri;

        if ($redirectUri === null || $redirectUri === '') {
            throw AuthenticationException::missingRedirectUri();
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout($this->config->httpTimeout)
                ->post($this->config->tokenUrl(), [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $redirectUri,
                    'client_id' => $this->config->clientId,
                    'code_verifier' => $codeVerifier,
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

        return UserTokenSet::fromTokenResponse((array) $response->json());
    }
}
