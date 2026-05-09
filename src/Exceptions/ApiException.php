<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Exceptions;

use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;

class ApiException extends SpotifyException
{
    public static function fromResponse(Response $response): self
    {
        $body = (array) $response->json();
        $error = (array) ($body['error'] ?? []);

        $message = (string) (
            $error['message']
            ?? $body['error_description']
            ?? "Spotify API request failed with status {$response->status()}."
        );

        return match ($response->status()) {
            JsonResponse::HTTP_UNAUTHORIZED => new AuthenticationException($message, $response->status(), context: $body),
            JsonResponse::HTTP_TOO_MANY_REQUESTS => RateLimitException::fromResponse($response, $message),
            JsonResponse::HTTP_UNPROCESSABLE_ENTITY, JsonResponse::HTTP_BAD_REQUEST => new ValidationException($message, $response->status(), context: $body),
            default => new self($message, $response->status(), context: $body),
        };
    }
}
