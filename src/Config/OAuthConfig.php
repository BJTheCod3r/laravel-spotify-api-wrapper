<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Config;

final class OAuthConfig
{
    /**
     * @param list<string> $defaultScopes
     * @param list<string> $routesMiddleware
     */
    public function __construct(
        public readonly ?string $redirectUri,
        public readonly array $defaultScopes,
        public readonly ?string $guard,
        public readonly bool $routesEnabled,
        public readonly string $routesPrefix,
        public readonly array $routesMiddleware,
        public readonly string $sessionKey,
        public readonly string $afterConnect,
        public readonly string $afterDisconnect,
        public readonly bool $refreshLock,
        public readonly int $refreshLockTtl,
        public readonly int $refreshLockWait,
        public readonly ?string $tokenRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $routes = isset($config['routes']) && is_array($config['routes']) ? $config['routes'] : [];
        $lock = isset($config['refresh_lock']) && is_array($config['refresh_lock']) ? $config['refresh_lock'] : [];

        return new self(
            redirectUri: self::optionalString($config['redirect_uri'] ?? null),
            defaultScopes: self::stringList($config['default_scopes'] ?? []),
            guard: self::optionalString($config['guard'] ?? null),
            routesEnabled: (bool) ($routes['enabled'] ?? true),
            routesPrefix: trim((string) ($routes['prefix'] ?? 'spotify'), '/'),
            routesMiddleware: self::stringList($routes['middleware'] ?? ['web', 'auth']),
            sessionKey: (string) ($config['session_key'] ?? 'spotify.oauth'),
            afterConnect: (string) ($config['after_connect'] ?? '/'),
            afterDisconnect: (string) ($config['after_disconnect'] ?? '/'),
            refreshLock: (bool) ($lock['enabled'] ?? true),
            refreshLockTtl: (int) ($lock['ttl'] ?? 10),
            refreshLockWait: (int) ($lock['wait'] ?? 5),
            tokenRepository: self::optionalString($config['token_repository'] ?? null),
        );
    }

    private static function optionalString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];

        foreach ($value as $item) {
            $item = trim((string) $item);

            if ($item !== '') {
                $out[] = $item;
            }
        }

        return array_values(array_unique($out));
    }
}
