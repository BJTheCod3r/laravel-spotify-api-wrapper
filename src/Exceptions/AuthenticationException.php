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
