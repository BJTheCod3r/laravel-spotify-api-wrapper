<?php

declare(strict_types=1);

use BjTheCod3r\Spotify\Events\SpotifyConnected;
use BjTheCod3r\Spotify\Events\SpotifyConnectFailed;
use BjTheCod3r\Spotify\Models\SpotifyUserToken;
use BjTheCod3r\Spotify\Tests\Stubs\TestUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Http::preventStrayRequests();
});

it('exchanges the code, captures the Spotify user id, and persists encrypted tokens', function (): void {
    Event::fake([SpotifyConnected::class, SpotifyConnectFailed::class]);

    Http::fake([
        'accounts.spotify.com/api/token' => Http::response([
            'access_token' => 'fresh-access',
            'refresh_token' => 'fresh-refresh',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => 'user-read-private user-library-read',
        ]),
        'api.spotify.com/v1/me' => Http::response([
            'id' => 'spotify-user-42',
            'display_name' => 'Test',
            'type' => 'user',
            'href' => 'https://api.spotify.com/v1/users/spotify-user-42',
            'uri' => 'spotify:user:spotify-user-42',
        ]),
    ]);

    $this->actingAs(new TestUser(id: 42));

    $response = $this->withSession([
        'spotify.oauth.state' => 'expected-state',
        'spotify.oauth.verifier' => 'expected-verifier',
        'spotify.oauth.scopes' => ['user-read-private'],
    ])->get('/spotify/callback?code=auth-code&state=expected-state');

    $response->assertStatus(302);
    $response->assertRedirect('/');

    Http::assertSent(function ($request): bool {
        if (! str_contains($request->url(), 'accounts.spotify.com/api/token')) {
            return false;
        }

        $body = $request->body();
        parse_str($body, $parsed);

        return ($parsed['grant_type'] ?? null) === 'authorization_code'
            && ($parsed['code'] ?? null) === 'auth-code'
            && ($parsed['code_verifier'] ?? null) === 'expected-verifier'
            && ($parsed['client_id'] ?? null) === 'test-client-id';
    });

    $row = DB::table('spotify_user_tokens')->where('user_id', '42')->first();
    expect($row)->not->toBeNull();
    expect($row->access_token)->not->toBe('fresh-access');
    expect($row->refresh_token)->not->toBe('fresh-refresh');
    expect($row->spotify_user_id)->toBe('spotify-user-42');

    $model = SpotifyUserToken::query()->where('user_id', '42')->first();
    expect($model->access_token)->toBe('fresh-access');
    expect($model->refresh_token)->toBe('fresh-refresh');
    expect($model->scopes)->toEqual(['user-read-private', 'user-library-read']);

    Event::assertDispatched(SpotifyConnected::class, function (SpotifyConnected $event): bool {
        return (string) $event->userId === '42'
            && $event->spotifyUserId === 'spotify-user-42'
            && $event->scopes === ['user-read-private', 'user-library-read'];
    });

    Event::assertNotDispatched(SpotifyConnectFailed::class);

    expect(session()->has('spotify.oauth.state'))->toBeFalse();
    expect(session()->has('spotify.oauth.verifier'))->toBeFalse();
});

it('fires SpotifyConnectFailed on state mismatch', function (): void {
    Event::fake([SpotifyConnected::class, SpotifyConnectFailed::class]);

    $this->actingAs(new TestUser(id: 1));

    $response = $this->withSession([
        'spotify.oauth.state' => 'expected',
        'spotify.oauth.verifier' => 'verifier',
        'spotify.oauth.scopes' => [],
    ])->get('/spotify/callback?code=anything&state=tampered');

    $response->assertRedirect('/');

    Event::assertDispatched(SpotifyConnectFailed::class, function (SpotifyConnectFailed $event): bool {
        return $event->reason === SpotifyConnectFailed::REASON_STATE_MISMATCH;
    });

    Event::assertNotDispatched(SpotifyConnected::class);
    expect(DB::table('spotify_user_tokens')->count())->toBe(0);
});

it('fires SpotifyConnectFailed when the user denies consent', function (): void {
    Event::fake([SpotifyConnected::class, SpotifyConnectFailed::class]);

    $this->actingAs(new TestUser(id: 1));

    $response = $this->withSession([
        'spotify.oauth.state' => 'expected',
        'spotify.oauth.verifier' => 'verifier',
        'spotify.oauth.scopes' => [],
    ])->get('/spotify/callback?error=access_denied&state=expected');

    $response->assertRedirect('/');

    Event::assertDispatched(SpotifyConnectFailed::class, function (SpotifyConnectFailed $event): bool {
        return $event->reason === SpotifyConnectFailed::REASON_USER_DENIED
            && $event->description === 'access_denied';
    });
});
