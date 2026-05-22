<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Config;

final class SpotifyConfig
{
    public function __construct(
        public readonly string $clientId,
        public readonly string $clientSecret,
        public readonly string $accountsUrl,
        public readonly string $apiUrl,
        public readonly ?string $defaultMarket,
        public readonly ?string $defaultLocale,
        public readonly ?int $defaultLimit,
        public readonly ?string $cacheStore,
        public readonly string $cacheKey,
        public readonly int $cacheTtlBuffer,
        public readonly int $httpTimeout,
        public readonly int $httpRetryTimes,
        public readonly int $httpRetrySleep,
        public readonly OAuthConfig $oauth,
    ) {
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $endpoints = isset($config['endpoints']) && is_array($config['endpoints']) ? $config['endpoints'] : [];
        $defaults = isset($config['defaults']) && is_array($config['defaults']) ? $config['defaults'] : [];
        $cache = isset($config['cache']) && is_array($config['cache']) ? $config['cache'] : [];
        $http = isset($config['http']) && is_array($config['http']) ? $config['http'] : [];
        $retry = isset($http['retry']) && is_array($http['retry']) ? $http['retry'] : [];
        $oauth = isset($config['oauth']) && is_array($config['oauth']) ? $config['oauth'] : [];

        return new self(
            clientId: isset($config['client_id']) ? trim((string) $config['client_id']) : '',
            clientSecret: isset($config['client_secret']) ? trim((string) $config['client_secret']) : '',
            accountsUrl: rtrim((string) ($endpoints['accounts'] ?? 'https://accounts.spotify.com'), '/'),
            apiUrl: rtrim((string) ($endpoints['api'] ?? 'https://api.spotify.com/v1'), '/'),
            defaultMarket: self::optionalString($defaults['market'] ?? null),
            defaultLocale: self::optionalString($defaults['locale'] ?? null),
            defaultLimit: self::optionalInt($defaults['limit'] ?? null),
            cacheStore: self::optionalString($cache['store'] ?? null),
            cacheKey: (string) ($cache['key'] ?? 'spotify.client_credentials.token'),
            cacheTtlBuffer: (int) ($cache['ttl_buffer'] ?? 60),
            httpTimeout: (int) ($http['timeout'] ?? 10),
            httpRetryTimes: (int) ($retry['times'] ?? 1),
            httpRetrySleep: (int) ($retry['sleep'] ?? 200),
            oauth: OAuthConfig::fromArray($oauth),
        );
    }

    public function authorizeUrl(): string
    {
        return $this->accountsUrl.'/authorize';
    }

    public function tokenUrl(): string
    {
        return $this->accountsUrl.'/api/token';
    }

    private static function optionalString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private static function optionalInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
