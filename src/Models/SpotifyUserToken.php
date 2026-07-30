<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $user_id
 * @property ?string $spotify_user_id
 * @property string $access_token
 * @property string $refresh_token
 * @property string $token_type
 * @property array<int, string> $scopes
 * @property \Carbon\CarbonInterface $expires_at
 */
class SpotifyUserToken extends Model
{
    protected $table = 'spotify_user_tokens';

    protected $fillable = [
        'user_id',
        'spotify_user_id',
        'access_token',
        'refresh_token',
        'token_type',
        'scopes',
        'expires_at',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'scopes' => 'array',
        'expires_at' => 'datetime',
    ];
}
