<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Events;

final class SpotifyDisconnected
{
    public const REASON_USER = 'user';

    public const REASON_INVALID_GRANT = 'invalid_grant';

    public function __construct(
        public readonly int|string $userId,
        public readonly string $reason = self::REASON_USER,
    ) {
    }
}
