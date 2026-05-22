<?php

declare(strict_types=1);

use BjTheCod3r\Spotify\Auth\OAuthManager;
use BjTheCod3r\Spotify\Auth\UserTokenSet;
use BjTheCod3r\Spotify\Contracts\UserTokenRepository;
use BjTheCod3r\Spotify\Events\SpotifyDisconnected;
use BjTheCod3r\Spotify\Events\SpotifyTokenRefreshed;
use BjTheCod3r\Spotify\Exceptions\AuthenticationException;
use BjTheCod3r\Spotify\Facades\Spotify;
use BjTheCod3r\Spotify\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Http::preventStrayRequests();
});

function storeTokens(int|string $userId, array $overrides = []): void
{
    $repo = app(UserTokenRepository::class);
    $repo->store($userId, new UserTokenSet(
        accessToken: $overrides['access'] ?? 'live-access',
        refreshToken: $overrides['refresh'] ?? 'live-refresh',
        expiresAt: $overrides['expires_at'] ?? Carbon::now()->addHour(),
        scopes: $overrides['scopes'] ?? ['user-read-private'],
        spotifyUserId: $overrides['spotify_user_id'] ?? 'spotify-user-1',
    ));
}

it('reuses the stored token without refreshing when not expired', function (): void {
    storeTokens(1);

    Http::fake([
        'api.spotify.com/v1/me' => Http::response([
            'id' => 'spotify-user-1',
            'display_name' => 'A',
            'type' => 'user',
            'href' => 'h',
            'uri' => 'u',
        ]),
    ]);

    $profile = Spotify::asUser(1)->me()->profile()->get();

    expect($profile)->toBeInstanceOf(User::class);

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'api.spotify.com/v1/me')
        && $request->header('Authorization')[0] === 'Bearer live-access');

    Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'accounts.spotify.com/api/token'));
});

it('refreshes an expired token and preserves the existing refresh token when Spotify omits a new one', function (): void {
    Event::fake([SpotifyTokenRefreshed::class]);

    storeTokens(1, ['expires_at' => Carbon::now()->subMinute()]);

    Http::fake([
        'accounts.spotify.com/api/token' => Http::response([
            'access_token' => 'refreshed-access',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => 'user-read-private',
        ]),
        'api.spotify.com/v1/me' => Http::response([
            'id' => 'spotify-user-1',
            'display_name' => 'A',
            'type' => 'user',
            'href' => 'h',
            'uri' => 'u',
        ]),
    ]);

    Spotify::asUser(1)->me()->profile()->get();

    $current = app(UserTokenRepository::class)->find(1);
    expect($current->accessToken)->toBe('refreshed-access');
    expect($current->refreshToken)->toBe('live-refresh');

    Event::assertDispatched(SpotifyTokenRefreshed::class);
});

it('rotates the stored refresh token when Spotify returns a new one', function (): void {
    storeTokens(1, ['expires_at' => Carbon::now()->subMinute()]);

    Http::fake([
        'accounts.spotify.com/api/token' => Http::response([
            'access_token' => 'rotated-access',
            'refresh_token' => 'rotated-refresh',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]),
        'api.spotify.com/v1/me' => Http::response([
            'id' => 'spotify-user-1',
            'display_name' => 'A',
            'type' => 'user',
            'href' => 'h',
            'uri' => 'u',
        ]),
    ]);

    Spotify::asUser(1)->me()->profile()->get();

    $current = app(UserTokenRepository::class)->find(1);
    expect($current->refreshToken)->toBe('rotated-refresh');
});

it('refreshes once on a 401 and retries the original request', function (): void {
    storeTokens(1);

    Http::fake([
        'accounts.spotify.com/api/token' => Http::response([
            'access_token' => 'after-401-access',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]),
        'api.spotify.com/v1/me' => Http::sequence()
            ->push(['error' => ['status' => 401, 'message' => 'Unauthorized']], 401)
            ->push([
                'id' => 'spotify-user-1',
                'display_name' => 'A',
                'type' => 'user',
                'href' => 'h',
                'uri' => 'u',
            ], 200),
    ]);

    $profile = Spotify::asUser(1)->me()->profile()->get();

    expect($profile)->toBeInstanceOf(User::class);

    $tokenCalls = 0;
    $meCalls = 0;
    foreach (Http::recorded() as [$request]) {
        if (str_contains($request->url(), 'accounts.spotify.com/api/token')) {
            $tokenCalls++;
        }
        if (str_contains($request->url(), 'api.spotify.com/v1/me')) {
            $meCalls++;
        }
    }

    expect($tokenCalls)->toBe(1);
    expect($meCalls)->toBe(2);
});

it('disconnects on invalid_grant and dispatches SpotifyDisconnected', function (): void {
    Event::fake([SpotifyDisconnected::class]);

    storeTokens(1, ['expires_at' => Carbon::now()->subMinute()]);

    Http::fake([
        'accounts.spotify.com/api/token' => Http::response([
            'error' => 'invalid_grant',
            'error_description' => 'Refresh token revoked.',
        ], 400),
    ]);

    expect(fn () => app(OAuthManager::class)->providerFor(1)->token())
        ->toThrow(AuthenticationException::class);

    expect(app(UserTokenRepository::class)->find(1))->toBeNull();

    Event::assertDispatched(SpotifyDisconnected::class, function (SpotifyDisconnected $event): bool {
        return $event->reason === SpotifyDisconnected::REASON_INVALID_GRANT;
    });
});

it('throws AuthenticationException when no tokens are stored for the user', function (): void {
    expect(fn () => app(OAuthManager::class)->providerFor(99)->token())
        ->toThrow(AuthenticationException::class);
});
