<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Auth;

use BjTheCod3r\Spotify\Exceptions\AuthenticationException;
use Illuminate\Support\Carbon;

final readonly class UserTokenSet
{
    /**
     * @param list<string> $scopes
     */
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public Carbon $expiresAt,
        public array $scopes,
        public ?string $spotifyUserId = null,
        public string $tokenType = 'Bearer',
    ) {
    }

    public function isExpired(int $bufferSeconds = 0): bool
    {
        return $this->expiresAt->copy()->subSeconds($bufferSeconds)->isPast();
    }

    public function authorizationHeader(): string
    {
        return ucfirst($this->tokenType).' '.$this->accessToken;
    }

    /**
     * Spotify omits `refresh_token` when it doesn't rotate. Carry the previous
     * one forward in that case, and capture a newly-issued one when present.
     *
     * @param array<string, mixed> $payload
     */
    public function refreshedWith(array $payload): self
    {
        $expiresIn = (int) ($payload['expires_in'] ?? 3600);
        $scope = isset($payload['scope']) ? (string) $payload['scope'] : null;

        return new self(
            accessToken: (string) ($payload['access_token'] ?? ''),
            refreshToken: isset($payload['refresh_token']) && $payload['refresh_token'] !== ''
                ? (string) $payload['refresh_token']
                : $this->refreshToken,
            expiresAt: Carbon::now()->addSeconds($expiresIn),
            scopes: $scope !== null && $scope !== ''
                ? self::splitScopes($scope)
                : $this->scopes,
            spotifyUserId: $this->spotifyUserId,
            tokenType: (string) ($payload['token_type'] ?? $this->tokenType),
        );
    }

    public function withSpotifyUserId(string $spotifyUserId): self
    {
        return new self(
            accessToken: $this->accessToken,
            refreshToken: $this->refreshToken,
            expiresAt: $this->expiresAt,
            scopes: $this->scopes,
            spotifyUserId: $spotifyUserId,
            tokenType: $this->tokenType,
        );
    }

    /**
     * @param array<string, mixed> $payload Spotify /api/token response.
     */
    public static function fromTokenResponse(array $payload): self
    {
        $accessToken = isset($payload['access_token']) ? (string) $payload['access_token'] : '';
        $refreshToken = isset($payload['refresh_token']) ? (string) $payload['refresh_token'] : '';

        // The PKCE code exchange must yield both tokens; a 200 with either
        // missing is a malformed response we shouldn't quietly persist.
        if ($accessToken === '' || $refreshToken === '') {
            throw AuthenticationException::malformedTokenResponse($payload);
        }

        $expiresIn = (int) ($payload['expires_in'] ?? 3600);
        $scope = isset($payload['scope']) ? (string) $payload['scope'] : '';

        return new self(
            accessToken: $accessToken,
            refreshToken: $refreshToken,
            expiresAt: Carbon::now()->addSeconds($expiresIn),
            scopes: self::splitScopes($scope),
            tokenType: (string) ($payload['token_type'] ?? 'Bearer'),
        );
    }

    /**
     * @return array{access_token: string, refresh_token: string, expires_at: int, scopes: list<string>, spotify_user_id: ?string, token_type: string}
     */
    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'expires_at' => $this->expiresAt->getTimestamp(),
            'scopes' => $this->scopes,
            'spotify_user_id' => $this->spotifyUserId,
            'token_type' => $this->tokenType,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $rawScopes = $data['scopes'] ?? [];
        $scopes = is_array($rawScopes) ? array_values(array_map('strval', $rawScopes)) : [];

        return new self(
            accessToken: (string) ($data['access_token'] ?? ''),
            refreshToken: (string) ($data['refresh_token'] ?? ''),
            expiresAt: Carbon::createFromTimestamp((int) ($data['expires_at'] ?? 0)),
            scopes: $scopes,
            spotifyUserId: isset($data['spotify_user_id']) && $data['spotify_user_id'] !== ''
                ? (string) $data['spotify_user_id']
                : null,
            tokenType: (string) ($data['token_type'] ?? 'Bearer'),
        );
    }

    /**
     * @return list<string>
     */
    private static function splitScopes(string $scope): array
    {
        $parts = preg_split('/\s+/', trim($scope)) ?: [];

        return array_values(array_filter($parts, static fn (string $s): bool => $s !== ''));
    }
}
