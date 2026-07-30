<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Exceptions;

use Throwable;

class AuthenticationException extends SpotifyException
{
    public static function missingCredentials(): self
    {
        return new self(
            'Spotify client credentials are not configured. Set SPOTIFY_CLIENT_ID and SPOTIFY_CLIENT_SECRET in your environment.',
        );
    }

    public static function missingRedirectUri(): self
    {
        return new self(
            'Spotify OAuth redirect URI is not configured. Set SPOTIFY_REDIRECT_URI in your environment or update config/spotify.php.',
        );
    }

    public static function noAuthenticatedUser(): self
    {
        return new self(
            'No authenticated user. Resolve one via the configured guard or call Spotify::asUser($id) explicitly.',
        );
    }

    public static function notConnected(string|int $userId): self
    {
        return new self(
            "User [{$userId}] has not connected a Spotify account.",
        );
    }

    public static function unknownSpotifyUser(string|int $userId): self
    {
        return new self(
            "Could not resolve the Spotify account id for user [{$userId}]. Have them reconnect their Spotify account.",
        );
    }

    public static function stateMismatch(): self
    {
        return new self('Spotify OAuth state mismatch. The callback request did not match the originating session.');
    }

    public static function userDenied(?string $reason = null): self
    {
        $suffix = $reason !== null && $reason !== '' ? " ({$reason})" : '';

        return new self('User denied the Spotify authorization request'.$suffix.'.');
    }

    public static function authorizeError(string $error): self
    {
        return new self("Spotify authorization failed: {$error}.");
    }

    public static function invalidGrant(): self
    {
        return new self(
            'Spotify rejected the refresh token (invalid_grant). The user must reconnect their account.',
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function malformedTokenResponse(array $payload): self
    {
        return new self(
            'Spotify returned a 200 response without a usable access/refresh token pair.',
            context: $payload,
        );
    }

    public static function connectionFailed(Throwable $previous): self
    {
        return new self(
            'Could not reach Spotify accounts service to obtain an access token.',
            previous: $previous,
        );
    }

    public static function unexpected(Throwable $previous): self
    {
        return new self(
            'Unexpected error while obtaining a Spotify access token: '.$previous->getMessage(),
            previous: $previous,
        );
    }

    /**
     * @param array<string, mixed> $body
     */
    public static function fromResponse(int $status, array $body): self
    {
        $message = (string) (
            $body['error_description']
            ?? $body['error']
            ?? "Spotify authentication failed with status {$status}."
        );

        return new self($message, $status, context: $body);
    }
}
