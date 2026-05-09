<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Exceptions;

use Illuminate\Http\Client\Response;

class RateLimitException extends SpotifyException
{
    public ?int $retryAfter = null;

    public static function fromResponse(Response $response, string $message): self
    {
        $retryAfter = $response->header('Retry-After');

        $exception = new self(
            message: $message,
            code: $response->status(),
            context: (array) $response->json(),
        );

        $exception->retryAfter = is_numeric($retryAfter) ? (int) $retryAfter : null;

        return $exception;
    }
}
