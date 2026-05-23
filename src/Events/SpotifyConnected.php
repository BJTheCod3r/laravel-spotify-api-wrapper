<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Events;

final class SpotifyConnected
{
    /**
     * @param list<string> $scopes Granted scopes echoed by Spotify (not requested).
     */
    public function __construct(
        public readonly int|string $userId,
        public readonly ?string $spotifyUserId,
        public readonly array $scopes,
    ) {
    }
}
