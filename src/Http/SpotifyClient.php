<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Http;

use BjTheCod3r\Spotify\Config\SpotifyConfig;
use BjTheCod3r\Spotify\Contracts\TokenProvider;
use BjTheCod3r\Spotify\Exceptions\ApiException;
use BjTheCod3r\Spotify\Exceptions\SpotifyException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Throwable;

class SpotifyClient
{
    public function __construct(
        protected TokenProvider $tokenProvider,
        protected SpotifyConfig $config,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        return $this->send('GET', $path, ['query' => $this->cleanQuery($query)]);
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function post(string $path, array $body = [], array $query = []): array
    {
        return $this->send('POST', $path, [
            'json' => $body,
            'query' => $this->cleanQuery($query),
        ]);
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function put(string $path, array $body = [], array $query = []): array
    {
        return $this->send('PUT', $path, [
            'json' => $body,
            'query' => $this->cleanQuery($query),
        ]);
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function delete(string $path, array $body = [], array $query = []): array
    {
        return $this->send('DELETE', $path, [
            'json' => $body,
            'query' => $this->cleanQuery($query),
        ]);
    }

    /**
     * @param array{query?: array<string, mixed>, json?: array<string, mixed>} $options
     *
     * @return array<string, mixed>
     */
    protected function send(string $method, string $path, array $options, bool $retried = false): array
    {
        try {
            $response = $this->pendingRequest()
                ->send(strtoupper($method), $this->url($path), $options);
        } catch (ConnectionException $e) {
            throw new SpotifyException(
                'Could not reach Spotify API: '.$e->getMessage(),
                previous: $e,
            );
        } catch (Throwable $e) {
            throw new SpotifyException(
                'Unexpected error while calling Spotify API: '.$e->getMessage(),
                previous: $e,
            );
        }

        if ($response->status() === JsonResponse::HTTP_UNAUTHORIZED && ! $retried) {
            $this->tokenProvider->forget();

            return $this->send($method, $path, $options, retried: true);
        }

        if ($response->failed()) {
            throw ApiException::fromResponse($response);
        }

        if ($response->status() === JsonResponse::HTTP_NO_CONTENT) {
            return [];
        }

        return $this->decode($response);
    }

    protected function pendingRequest(): PendingRequest
    {
        return Http::baseUrl($this->config->apiUrl)
            ->timeout($this->config->httpTimeout)
            ->retry($this->config->httpRetryTimes, $this->config->httpRetrySleep, throw: false)
            ->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => $this->tokenProvider->token()->authorizationHeader(),
            ])
            ->acceptJson();
    }

    protected function url(string $path): string
    {
        return '/'.ltrim($path, '/');
    }

    /**
     * Drop null/empty values so they don't end up as `?foo=` query strings.
     *
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    protected function cleanQuery(array $query): array
    {
        return array_filter(
            $query,
            static fn (mixed $value): bool => $value !== null && $value !== '' && $value !== [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function decode(Response $response): array
    {
        $decoded = $response->json();

        return is_array($decoded) ? $decoded : [];
    }
}
