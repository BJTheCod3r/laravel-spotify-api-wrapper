<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Events;

use Illuminate\Support\Carbon;

final class SpotifyTokenRefreshed
{
    public function __construct(
        public readonly int|string $userId,
        public readonly Carbon $expiresAt,
    ) {
    }
}
