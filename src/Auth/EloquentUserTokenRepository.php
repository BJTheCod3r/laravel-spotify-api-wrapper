<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Auth;

use BjTheCod3r\Spotify\Contracts\UserTokenRepository;
use BjTheCod3r\Spotify\Models\SpotifyUserToken;

final class EloquentUserTokenRepository implements UserTokenRepository
{
    public function find(int|string $userId): ?UserTokenSet
    {
        $row = SpotifyUserToken::query()
            ->where('user_id', (string) $userId)
            ->first();

        if ($row === null) {
            return null;
        }

        $scopes = is_array($row->scopes) ? array_values(array_map('strval', $row->scopes)) : [];

        return new UserTokenSet(
            accessToken: $row->access_token,
            refreshToken: $row->refresh_token,
            expiresAt: $row->expires_at,
            scopes: $scopes,
            spotifyUserId: $row->spotify_user_id,
            tokenType: $row->token_type ?? 'Bearer',
        );
    }

    public function store(int|string $userId, UserTokenSet $tokens): void
    {
        SpotifyUserToken::query()->updateOrCreate(
            ['user_id' => (string) $userId],
            [
                'spotify_user_id' => $tokens->spotifyUserId,
                'access_token' => $tokens->accessToken,
                'refresh_token' => $tokens->refreshToken,
                'token_type' => $tokens->tokenType,
                'scopes' => $tokens->scopes,
                'expires_at' => $tokens->expiresAt,
            ],
        );
    }

    public function forget(int|string $userId): void
    {
        SpotifyUserToken::query()
            ->where('user_id', (string) $userId)
            ->delete();
    }
}
