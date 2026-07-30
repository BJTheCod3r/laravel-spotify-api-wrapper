<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Auth;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

final readonly class Token
{
    public function __construct(
        public string $accessToken,
        public string $tokenType,
        public CarbonInterface $expiresAt,
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
     * @return array{access_token: string, token_type: string, expires_at: int}
     */
    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'token_type' => $this->tokenType,
            'expires_at' => $this->expiresAt->getTimestamp(),
        ];
    }

    /**
     * @param array{access_token: string, token_type: string, expires_at: int} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            accessToken: $data['access_token'],
            tokenType: $data['token_type'],
            expiresAt: Date::createFromTimestamp($data['expires_at']),
        );
    }
}
