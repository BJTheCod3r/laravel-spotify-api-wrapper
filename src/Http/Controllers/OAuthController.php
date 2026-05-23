<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Http\Controllers;

use BjTheCod3r\Spotify\Config\SpotifyConfig;
use BjTheCod3r\Spotify\Exceptions\AuthenticationException;
use BjTheCod3r\Spotify\Spotify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class OAuthController extends Controller
{
    public function __construct(
        protected Spotify $spotify,
        protected SpotifyConfig $config,
    ) {
    }

    public function connect(Request $request): RedirectResponse
    {
        $scopes = $this->parseScopes($request->query('scopes'));

        return $this->spotify->redirect($request, $scopes);
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $this->spotify->handleCallback($request);
        } catch (AuthenticationException) {
            // Event already fired by OAuthManager; surface as a clean redirect.
        }

        return new RedirectResponse($this->config->oauth->afterConnect);
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $this->spotify->disconnect();

        return new RedirectResponse($this->config->oauth->afterDisconnect);
    }

    /**
     * @return list<string>|null
     */
    private function parseScopes(mixed $value): ?array
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $parts = preg_split('/[\s,]+/', $value) ?: [];

        $clean = array_values(array_filter(array_map('trim', $parts), static fn (string $s): bool => $s !== ''));

        return $clean === [] ? null : $clean;
    }
}
