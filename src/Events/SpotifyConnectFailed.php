<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Events;

final class SpotifyConnectFailed
{
    public const REASON_STATE_MISMATCH = 'state_mismatch';

    /**
     * Spotify returned `error=access_denied` — the user clicked "Cancel"
     * on the consent screen.
     */
    public const REASON_USER_DENIED = 'user_denied';

    /**
     * Spotify returned a different `?error=` code on the callback
     * (invalid_scope, server_error, temporarily_unavailable, …). The
     * specific code is in `description`.
     */
    public const REASON_AUTHORIZE_ERROR = 'authorize_error';

    public const REASON_EXCHANGE_FAILED = 'exchange_failed';

    public function __construct(
        public readonly int|string|null $userId,
        public readonly string $reason,
        public readonly ?string $description = null,
    ) {
    }
}
