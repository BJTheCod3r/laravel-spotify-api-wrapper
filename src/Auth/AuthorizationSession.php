<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Auth;

use Illuminate\Contracts\Session\Session;

/**
 * Tiny wrapper around the session keys used during the PKCE handshake.
 * The verifier and state are written before the redirect and read on the
 * callback; consume() clears them whether the callback succeeds or fails.
 */
final class AuthorizationSession
{
    public function __construct(
        private readonly Session $session,
        private readonly string $namespace,
    ) {
    }

    /**
     * @param list<string> $scopes
     */
    public function put(string $state, string $verifier, array $scopes): void
    {
        $this->session->put($this->key('state'), $state);
        $this->session->put($this->key('verifier'), $verifier);
        $this->session->put($this->key('scopes'), $scopes);
    }

    public function state(): ?string
    {
        $value = $this->session->get($this->key('state'));

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function verifier(): ?string
    {
        $value = $this->session->get($this->key('verifier'));

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function consume(): void
    {
        $this->session->forget([
            $this->key('state'),
            $this->key('verifier'),
            $this->key('scopes'),
        ]);
    }

    /**
     * Flash callback failure info so the after-connect destination can
     * render error UX without needing a separate event subscriber. Read via
     * `session('spotify.oauth.error')` (with the configured session_key).
     */
    public function flashError(string $reason, ?string $description = null): void
    {
        $this->session->flash($this->key('error'), [
            'reason' => $reason,
            'description' => $description,
        ]);
    }

    private function key(string $part): string
    {
        return $this->namespace.'.'.$part;
    }
}
