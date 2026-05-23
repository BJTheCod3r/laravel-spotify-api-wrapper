<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Auth;

use BjTheCod3r\Spotify\Config\SpotifyConfig;
use BjTheCod3r\Spotify\Exceptions\AuthenticationException;

final class AuthorizationUrlBuilder
{
    public function __construct(private readonly SpotifyConfig $config)
    {
    }

    /**
     * @param list<string> $scopes
     */
    public function build(string $state, string $codeChallenge, array $scopes): string
    {
        if ($this->config->clientId === '') {
            throw AuthenticationException::missingCredentials();
        }

        $redirectUri = $this->config->oauth->redirectUri;

        if ($redirectUri === null || $redirectUri === '') {
            throw AuthenticationException::missingRedirectUri();
        }

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->config->clientId,
            'scope' => implode(' ', $scopes),
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'code_challenge_method' => 'S256',
            'code_challenge' => $codeChallenge,
        ]);

        return $this->config->authorizeUrl().'?'.$query;
    }
}
