<?php

declare(strict_types=1);

use BjTheCod3r\Spotify\Auth\UserTokenSet;
use BjTheCod3r\Spotify\Contracts\UserTokenRepository;
use BjTheCod3r\Spotify\Exceptions\ValidationException;
use BjTheCod3r\Spotify\Facades\Spotify;
use BjTheCod3r\Spotify\Resources\Paginated;
use BjTheCod3r\Spotify\Resources\Playlist;
use BjTheCod3r\Spotify\Resources\PlaylistTrackItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Http::preventStrayRequests();

    app(UserTokenRepository::class)->store(1, new UserTokenSet(
        accessToken: 'live-access',
        refreshToken: 'live-refresh',
        expiresAt: Carbon::now()->addHour(),
        scopes: ['playlist-modify-public', 'playlist-modify-private', 'playlist-read-private'],
        spotifyUserId: 'spotify-user-1',
    ));
});

function fakePlaylist(array $overrides = []): array
{
    return array_merge([
        'id' => 'pl1',
        'name' => 'Top 100',
        'href' => 'https://api.spotify.com/v1/playlists/pl1',
        'uri' => 'spotify:playlist:pl1',
        'type' => 'playlist',
        'description' => 'The best of the year',
        'public' => false,
        'snapshot_id' => 'snap-1',
        'owner' => ['id' => 'spotify-user-1', 'type' => 'user'],
    ], $overrides);
}

it('creates a playlist on the connected account', function (): void {
    Http::fake([
        'api.spotify.com/v1/users/spotify-user-1/playlists' => Http::response(fakePlaylist(), 201),
    ]);

    $playlist = Spotify::asUser(1)
        ->createPlaylist('Top 100')
        ->description('The best of the year')
        ->public(false)
        ->get();

    expect($playlist)->toBeInstanceOf(Playlist::class)
        ->and($playlist->id)->toBe('pl1')
        ->and($playlist->name)->toBe('Top 100');

    Http::assertSent(fn ($request): bool => $request->method() === 'POST'
        && $request->url() === 'https://api.spotify.com/v1/users/spotify-user-1/playlists'
        && $request->header('Authorization')[0] === 'Bearer live-access'
        && $request->data() === [
            'name' => 'Top 100',
            'description' => 'The best of the year',
            'public' => false,
        ]);
});

it('backfills the spotify account id when the stored token predates it', function (): void {
    app(UserTokenRepository::class)->store(2, new UserTokenSet(
        accessToken: 'other-access',
        refreshToken: 'other-refresh',
        expiresAt: Carbon::now()->addHour(),
        scopes: ['playlist-modify-private'],
        spotifyUserId: null,
    ));

    Http::fake([
        'api.spotify.com/v1/me' => Http::response(['id' => 'spotify-user-2', 'type' => 'user']),
        'api.spotify.com/v1/users/spotify-user-2/playlists' => Http::response(fakePlaylist(['id' => 'pl2']), 201),
    ]);

    $playlist = Spotify::asUser(2)->createPlaylist('Mixtape')->get();

    expect($playlist->id)->toBe('pl2')
        ->and(app(UserTokenRepository::class)->find(2)->spotifyUserId)->toBe('spotify-user-2');
});

it('refuses a collaborative public playlist', function (): void {
    Spotify::asUser(1)->createPlaylist('Shared')->collaborative()->public()->get();
})->throws(ValidationException::class, 'cannot be public');

it('refuses a blank playlist name', function (): void {
    Spotify::asUser(1)->createPlaylist('   ')->get();
})->throws(ValidationException::class, 'A playlist name is required.');

it('adds items and normalises ids and links to uris', function (): void {
    Http::fake([
        'api.spotify.com/v1/playlists/pl1/tracks' => Http::response(['snapshot_id' => 'snap-2']),
    ]);

    $snapshot = Spotify::asUser(1)->addPlaylistItems('pl1', [
        '4iV5W9uYEdYUVa79Axb7Rh',
        'spotify:track:1301WleyT98MSxVHPZCA6M',
        'https://open.spotify.com/intl-de/track/7ouMYWpwJ422jRcDASZB7P?si=abc',
        'https://open.spotify.com/episode/512ojhOuo1ktJprKbVcKyQ',
    ])->get();

    expect($snapshot)->toBe('snap-2');

    Http::assertSent(fn ($request): bool => $request->method() === 'POST'
        && $request->data() === [
            'uris' => [
                'spotify:track:4iV5W9uYEdYUVa79Axb7Rh',
                'spotify:track:1301WleyT98MSxVHPZCA6M',
                'spotify:track:7ouMYWpwJ422jRcDASZB7P',
                'spotify:episode:512ojhOuo1ktJprKbVcKyQ',
            ],
        ]);
});

it('adds items at a position', function (): void {
    Http::fake([
        'api.spotify.com/v1/playlists/pl1/tracks' => Http::response(['snapshot_id' => 'snap-3']),
    ]);

    Spotify::asUser(1)->addPlaylistItems('pl1', 'track-a')->position(0)->get();

    Http::assertSent(fn ($request): bool => $request->data() === [
        'uris' => ['spotify:track:track-a'],
        'position' => 0,
    ]);
});

it('refuses more than 100 items in one request', function (): void {
    $uris = array_map(static fn (int $i): string => "track-{$i}", range(1, 101));

    Spotify::asUser(1)->addPlaylistItems('pl1', $uris)->get();
})->throws(ValidationException::class, 'at most 100 items');

it('refuses an empty add', function (): void {
    Spotify::asUser(1)->addPlaylistItems('pl1', [])->get();
})->throws(ValidationException::class, 'At least one track URI is required.');

it('replaces the whole tracklist', function (): void {
    Http::fake([
        'api.spotify.com/v1/playlists/pl1/tracks' => Http::response(['snapshot_id' => 'snap-4']),
    ]);

    $snapshot = Spotify::asUser(1)->replacePlaylistItems('pl1', ['track-b', 'track-a'])->get();

    expect($snapshot)->toBe('snap-4');

    Http::assertSent(fn ($request): bool => $request->method() === 'PUT'
        && $request->data() === ['uris' => ['spotify:track:track-b', 'spotify:track:track-a']]);
});

it('clears a playlist with an empty replace', function (): void {
    Http::fake([
        'api.spotify.com/v1/playlists/pl1/tracks' => Http::response(['snapshot_id' => 'snap-5']),
    ]);

    Spotify::asUser(1)->replacePlaylistItems('pl1', [])->get();

    Http::assertSent(fn ($request): bool => $request->data() === ['uris' => []]);
});

it('reorders a run of items', function (): void {
    Http::fake([
        'api.spotify.com/v1/playlists/pl1/tracks' => Http::response(['snapshot_id' => 'snap-6']),
    ]);

    $snapshot = Spotify::asUser(1)
        ->reorderPlaylistItems('pl1')
        ->rangeStart(9)
        ->insertBefore(0)
        ->rangeLength(2)
        ->snapshotId('snap-5')
        ->get();

    expect($snapshot)->toBe('snap-6');

    Http::assertSent(fn ($request): bool => $request->method() === 'PUT'
        && $request->data() === [
            'range_start' => 9,
            'insert_before' => 0,
            'range_length' => 2,
            'snapshot_id' => 'snap-5',
        ]);
});

it('requires both ends of a reorder', function (): void {
    Spotify::asUser(1)->reorderPlaylistItems('pl1')->rangeStart(3)->get();
})->throws(ValidationException::class, 'rangeStart');

it('removes items by uri', function (): void {
    Http::fake([
        'api.spotify.com/v1/playlists/pl1/tracks' => Http::response(['snapshot_id' => 'snap-7']),
    ]);

    $snapshot = Spotify::asUser(1)
        ->removePlaylistItems('pl1', ['track-a'])
        ->snapshotId('snap-6')
        ->get();

    expect($snapshot)->toBe('snap-7');

    Http::assertSent(fn ($request): bool => $request->method() === 'DELETE'
        && $request->data() === [
            'tracks' => [['uri' => 'spotify:track:track-a']],
            'snapshot_id' => 'snap-6',
        ]);
});

it('renames a playlist', function (): void {
    Http::fake([
        'api.spotify.com/v1/playlists/pl1' => Http::response([], 200),
    ]);

    $updated = Spotify::asUser(1)
        ->updatePlaylist('pl1')
        ->name('Top 100: 2026')
        ->description('Rebuilt')
        ->get();

    expect($updated)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->method() === 'PUT'
        && $request->url() === 'https://api.spotify.com/v1/playlists/pl1'
        && $request->data() === ['name' => 'Top 100: 2026', 'description' => 'Rebuilt']);
});

it('refuses an update with nothing set', function (): void {
    Spotify::asUser(1)->updatePlaylist('pl1')->get();
})->throws(ValidationException::class, 'Nothing to update.');

it('pages through a playlist tracklist', function (): void {
    Http::fake([
        'accounts.spotify.com/api/token' => Http::response([
            'access_token' => 'fake-access-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]),
        'api.spotify.com/v1/playlists/pl1/tracks*' => Http::response([
            'href' => 'https://api.spotify.com/v1/playlists/pl1/tracks',
            'limit' => 50,
            'offset' => 50,
            'total' => 100,
            'next' => null,
            'previous' => null,
            'items' => [[
                'added_at' => '2026-01-01T00:00:00Z',
                'is_local' => false,
                'track' => ['id' => 't1', 'name' => 'Song', 'type' => 'track'],
            ]],
        ]),
    ]);

    $page = Spotify::playlistItems('pl1')->limit(50)->offset(50)->market('US')->get();

    expect($page)->toBeInstanceOf(Paginated::class)
        ->and($page->items->first())->toBeInstanceOf(PlaylistTrackItem::class)
        ->and($page->items->first()->track->name)->toBe('Song')
        ->and($page->total)->toBe(100);

    Http::assertSent(fn ($request): bool => $request->method() === 'GET'
        && str_contains($request->url(), 'offset=50')
        && str_contains($request->url(), 'market=US'));
});
