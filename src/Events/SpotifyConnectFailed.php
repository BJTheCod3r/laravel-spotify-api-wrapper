<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Events;

final class SpotifyConnectFailed
{
    public const REASON_STATE_MISMATCH = 'state_mismatch';

    public const REASON_USER_DENIED = 'user_denied';

    public const REASON_EXCHANGE_FAILED = 'exchange_failed';

    public function __construct(
        public readonly int|string|null $userId,
        public readonly string $reason,
        public readonly ?string $description = null,
    ) {
    }
}
