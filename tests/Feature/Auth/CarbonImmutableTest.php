<?php

declare(strict_types=1);

use BjTheCod3r\Spotify\Auth\UserTokenSet;
use BjTheCod3r\Spotify\Contracts\UserTokenRepository;
use BjTheCod3r\Spotify\Facades\Spotify;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;

/*
 * Applications that call Date::use(CarbonImmutable::class) get immutable
 * instances out of every `datetime` cast, so the Eloquent repository hands
 * one straight into UserTokenSet. Anything narrower than CarbonInterface
 * turns a stored token into a TypeError at read time.
 */

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Http::preventStrayRequests();
    Date::use(CarbonImmutable::class);
});

afterEach(function (): void {
    Date::useDefault();
});

it('reads back a stored token on an immutable-date application', function (): void {
    app(UserTokenRepository::class)->store(1, new UserTokenSet(
        accessToken: 'live-access',
        refreshToken: 'live-refresh',
        expiresAt: CarbonImmutable::now()->addHour(),
        scopes: ['playlist-modify-private'],
        spotifyUserId: 'spotify-user-1',
    ));

    $tokens = app(UserTokenRepository::class)->find(1);

    expect($tokens)->not->toBeNull()
        ->and($tokens->expiresAt)->toBeInstanceOf(CarbonImmutable::class)
        ->and($tokens->isExpired())->toBeFalse();
});

it('calls the API as a user whose stored token is immutable', function (): void {
    Http::fake([
        'api.spotify.com/v1/me' => Http::response(['id' => 'spotify-user-1', 'type' => 'user']),
    ]);

    app(UserTokenRepository::class)->store(1, new UserTokenSet(
        accessToken: 'live-access',
        refreshToken: 'live-refresh',
        expiresAt: CarbonImmutable::now()->addHour(),
        scopes: ['user-read-private'],
        spotifyUserId: 'spotify-user-1',
    ));

    expect(Spotify::asUser(1)->me()->profile()->get()->id)->toBe('spotify-user-1');
});

it('refreshes an expired immutable token', function (): void {
    Http::fake([
        'accounts.spotify.com/api/token' => Http::response([
            'access_token' => 'rotated-access',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]),
        'api.spotify.com/v1/me' => Http::response(['id' => 'spotify-user-1', 'type' => 'user']),
    ]);

    app(UserTokenRepository::class)->store(1, new UserTokenSet(
        accessToken: 'stale-access',
        refreshToken: 'live-refresh',
        expiresAt: CarbonImmutable::now()->subMinute(),
        scopes: ['user-read-private'],
        spotifyUserId: 'spotify-user-1',
    ));

    Spotify::asUser(1)->me()->profile()->get();

    expect(app(UserTokenRepository::class)->find(1)->accessToken)->toBe('rotated-access');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.spotify.com/v1/me'
        && $request->header('Authorization')[0] === 'Bearer rotated-access');
});
