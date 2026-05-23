<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Auth;

final class Pkce
{
    /**
     * Generate a PKCE verifier + S256 challenge pair.
     *
     * Verifier length is 64 chars (Spotify accepts 43–128, RFC 7636).
     *
     * @return array{verifier: string, challenge: string}
     */
    public static function generate(): array
    {
        $verifier = self::base64Url(random_bytes(48));
        $challenge = self::base64Url(hash('sha256', $verifier, binary: true));

        return ['verifier' => $verifier, 'challenge' => $challenge];
    }

    public static function state(): string
    {
        return self::base64Url(random_bytes(32));
    }

    private static function base64Url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
