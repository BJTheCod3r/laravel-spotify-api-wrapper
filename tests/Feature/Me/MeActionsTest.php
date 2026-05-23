<?php

declare(strict_types=1);

use BjTheCod3r\Spotify\Auth\UserTokenSet;
use BjTheCod3r\Spotify\Contracts\UserTokenRepository;
use BjTheCod3r\Spotify\Facades\Spotify;
use BjTheCod3r\Spotify\Resources\Album;
use BjTheCod3r\Spotify\Resources\Artist;
use BjTheCod3r\Spotify\Resources\Audiobook;
use BjTheCod3r\Spotify\Resources\Episode;
use BjTheCod3r\Spotify\Resources\Paginated;
use BjTheCod3r\Spotify\Resources\Show;
use BjTheCod3r\Spotify\Resources\SimplifiedPlaylist;
use BjTheCod3r\Spotify\Resources\Track;
use BjTheCod3r\Spotify\Resources\User;
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
        scopes: ['user-read-private', 'user-library-read', 'user-top-read', 'user-read-recently-played', 'user-follow-read', 'playlist-read-private'],
        spotifyUserId: 'spotify-user-1',
    ));
});

it('gets the current user profile', function (): void {
    Http::fake([
        'api.spotify.com/v1/me' => Http::response([
            'id' => 'spotify-user-1',
            'display_name' => 'Test User',
            'type' => 'user',
            'href' => 'https://api.spotify.com/v1/users/spotify-user-1',
            'uri' => 'spotify:user:spotify-user-1',
        ]),
    ]);

    $profile = Spotify::asUser(1)->me()->profile()->get();

    expect($profile)->toBeInstanceOf(User::class)
        ->and($profile->id)->toBe('spotify-user-1')
        ->and($profile->displayName)->toBe('Test User');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.spotify.com/v1/me'
        && $request->header('Authorization')[0] === 'Bearer live-access');
});

it('gets my playlists paginated', function (): void {
    Http::fake([
        'api.spotify.com/v1/me/playlists*' => Http::response([
            'href' => 'https://api.spotify.com/v1/me/playlists',
            'limit' => 5,
            'offset' => 0,
            'total' => 1,
            'next' => null,
            'previous' => null,
            'items' => [[
                'id' => 'p1',
                'name' => 'Roadtrip',
                'type' => 'playlist',
                'href' => 'h',
                'uri' => 'u',
            ]],
        ]),
    ]);

    $page = Spotify::asUser(1)->me()->playlists()->limit(5)->get();

    expect($page)->toBeInstanceOf(Paginated::class)
        ->and($page->items->first())->toBeInstanceOf(SimplifiedPlaylist::class)
        ->and($page->items->first()->name)->toBe('Roadtrip');

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'limit=5'));
});

it('gets saved tracks and unwraps the added_at envelope', function (): void {
    Http::fake([
        'api.spotify.com/v1/me/tracks*' => Http::response([
            'href' => 'https://api.spotify.com/v1/me/tracks',
            'limit' => 1,
            'offset' => 0,
            'total' => 1,
            'next' => null,
            'previous' => null,
            'items' => [[
                'added_at' => '2026-01-01T00:00:00Z',
                'track' => [
                    'id' => 't1',
                    'name' => 'Song',
                    'type' => 'track',
                    'duration_ms' => 200000,
                ],
            ]],
        ]),
    ]);

    $page = Spotify::asUser(1)->me()->savedTracks()->market('US')->get();

    expect($page->items->first())->toBeInstanceOf(Track::class)
        ->and($page->items->first()->name)->toBe('Song');
});

it('gets saved albums', function (): void {
    Http::fake([
        'api.spotify.com/v1/me/albums*' => Http::response([
            'items' => [[
                'added_at' => '2026-01-01T00:00:00Z',
                'album' => ['id' => 'a1', 'name' => 'Album', 'type' => 'album'],
            ]],
            'href' => '', 'limit' => 1, 'offset' => 0, 'total' => 1, 'next' => null, 'previous' => null,
        ]),
    ]);

    $page = Spotify::asUser(1)->me()->savedAlbums()->get();

    expect($page->items->first())->toBeInstanceOf(Album::class);
});

it('gets saved shows', function (): void {
    Http::fake([
        'api.spotify.com/v1/me/shows*' => Http::response([
            'items' => [[
                'added_at' => '2026-01-01T00:00:00Z',
                'show' => ['id' => 's1', 'name' => 'Show', 'type' => 'show'],
            ]],
            'href' => '', 'limit' => 1, 'offset' => 0, 'total' => 1, 'next' => null, 'previous' => null,
        ]),
    ]);

    $page = Spotify::asUser(1)->me()->savedShows()->get();

    expect($page->items->first())->toBeInstanceOf(Show::class);
});

it('gets saved episodes', function (): void {
    Http::fake([
        'api.spotify.com/v1/me/episodes*' => Http::response([
            'items' => [[
                'added_at' => '2026-01-01T00:00:00Z',
                'episode' => ['id' => 'e1', 'name' => 'Episode', 'type' => 'episode'],
            ]],
            'href' => '', 'limit' => 1, 'offset' => 0, 'total' => 1, 'next' => null, 'previous' => null,
        ]),
    ]);

    $page = Spotify::asUser(1)->me()->savedEpisodes()->get();

    expect($page->items->first())->toBeInstanceOf(Episode::class);
});

it('gets saved audiobooks', function (): void {
    Http::fake([
        'api.spotify.com/v1/me/audiobooks*' => Http::response([
            'items' => [[
                'id' => 'b1',
                'name' => 'Book',
                'type' => 'audiobook',
                'authors' => [['name' => 'X']],
                'narrators' => [['name' => 'Y']],
                'languages' => ['en'],
            ]],
            'href' => '', 'limit' => 1, 'offset' => 0, 'total' => 1, 'next' => null, 'previous' => null,
        ]),
    ]);

    $page = Spotify::asUser(1)->me()->savedAudiobooks()->get();

    expect($page->items->first())->toBeInstanceOf(Audiobook::class);
});

it('gets top tracks with time_range query', function (): void {
    Http::fake([
        'api.spotify.com/v1/me/top/tracks*' => Http::response([
            'items' => [['id' => 't1', 'name' => 'Top Track', 'type' => 'track']],
            'href' => '', 'limit' => 10, 'offset' => 0, 'total' => 1, 'next' => null, 'previous' => null,
        ]),
    ]);

    $page = Spotify::asUser(1)->me()->topTracks()->timeRange('short_term')->limit(10)->get();

    expect($page->items->first())->toBeInstanceOf(Track::class);
    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'time_range=short_term'));
});

it('gets top artists', function (): void {
    Http::fake([
        'api.spotify.com/v1/me/top/artists*' => Http::response([
            'items' => [['id' => 'a1', 'name' => 'Top Artist', 'type' => 'artist']],
            'href' => '', 'limit' => 10, 'offset' => 0, 'total' => 1, 'next' => null, 'previous' => null,
        ]),
    ]);

    $page = Spotify::asUser(1)->me()->topArtists()->get();

    expect($page->items->first())->toBeInstanceOf(Artist::class);
});

it('gets recently played and unwraps the track envelope', function (): void {
    Http::fake([
        'api.spotify.com/v1/me/player/recently-played*' => Http::response([
            'items' => [[
                'played_at' => '2026-05-21T12:00:00Z',
                'track' => ['id' => 't1', 'name' => 'Last Played', 'type' => 'track'],
                'context' => null,
            ]],
            'href' => '', 'limit' => 1, 'offset' => 0, 'total' => 1, 'next' => null, 'previous' => null,
        ]),
    ]);

    $page = Spotify::asUser(1)->me()->recentlyPlayed()->limit(5)->after(1700000000000)->get();

    expect($page->items->first())->toBeInstanceOf(Track::class)
        ->and($page->items->first()->name)->toBe('Last Played');

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'after=1700000000000'));
});

it('gets followed artists from the nested artists envelope', function (): void {
    Http::fake([
        'api.spotify.com/v1/me/following*' => Http::response([
            'artists' => [
                'items' => [['id' => 'a1', 'name' => 'Followed', 'type' => 'artist']],
                'href' => '', 'limit' => 1, 'offset' => 0, 'total' => 1, 'next' => null, 'previous' => null,
            ],
        ]),
    ]);

    $page = Spotify::asUser(1)->me()->followedArtists()->limit(1)->get();

    expect($page->items->first())->toBeInstanceOf(Artist::class)
        ->and($page->items->first()->name)->toBe('Followed');

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'type=artist'));
});
