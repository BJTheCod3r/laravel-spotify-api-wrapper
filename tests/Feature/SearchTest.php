<?php

declare(strict_types=1);

use BjTheCod3r\Spotify\Enums\SearchType;
use BjTheCod3r\Spotify\Exceptions\ApiException;
use BjTheCod3r\Spotify\Exceptions\AuthenticationException;
use BjTheCod3r\Spotify\Exceptions\RateLimitException;
use BjTheCod3r\Spotify\Exceptions\ValidationException;
use BjTheCod3r\Spotify\Facades\Spotify;
use BjTheCod3r\Spotify\Resources\Album;
use BjTheCod3r\Spotify\Resources\Artist;
use BjTheCod3r\Spotify\Resources\Audiobook;
use BjTheCod3r\Spotify\Resources\Author;
use BjTheCod3r\Spotify\Resources\Followers;
use BjTheCod3r\Spotify\Resources\Narrator;
use BjTheCod3r\Spotify\Resources\Paginated;
use BjTheCod3r\Spotify\Resources\Playlist;
use BjTheCod3r\Spotify\Resources\PlaylistItemsLink;
use BjTheCod3r\Spotify\Resources\PlaylistTrackItem;
use BjTheCod3r\Spotify\Resources\SearchResults;
use BjTheCod3r\Spotify\Resources\SimplifiedPlaylist;
use BjTheCod3r\Spotify\Resources\Track;
use BjTheCod3r\Spotify\Resources\TracksLink;
use BjTheCod3r\Spotify\Resources\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Http::preventStrayRequests();

    Http::fake([
        'accounts.spotify.com/api/token' => Http::response([
            'access_token' => 'fake-access-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]),
    ]);
});

it('performs a multi-type search with the documented query parameters', function (): void {
    Http::fake([
        'api.spotify.com/v1/search*' => Http::response([
            'tracks' => [
                'href' => 'https://api.spotify.com/v1/search?type=track',
                'items' => [['id' => 't1', 'name' => 'Doxy', 'type' => 'track']],
                'limit' => 10,
                'offset' => 0,
                'total' => 1,
                'next' => null,
                'previous' => null,
            ],
            'albums' => [
                'href' => 'https://api.spotify.com/v1/search?type=album',
                'items' => [['id' => 'a1', 'name' => 'Kind of Blue', 'type' => 'album']],
                'limit' => 10,
                'offset' => 0,
                'total' => 1,
                'next' => null,
                'previous' => null,
            ],
        ]),
    ]);

    $response = Spotify::search('remaster track:Doxy artist:Miles Davis', [SearchType::Track, SearchType::Album])
        ->market('ES')
        ->limit(10)
        ->offset(0)
        ->get();

    expect($response)->toBeInstanceOf(SearchResults::class)
        ->and($response->tracks)->toBeInstanceOf(Paginated::class)
        ->and($response->albums)->toBeInstanceOf(Paginated::class)
        ->and($response->artists)->toBeNull()
        ->and($response->tracks->items[0])->toBeInstanceOf(Track::class)
        ->and($response->tracks->items[0]->name)->toBe('Doxy')
        ->and($response->albums->items[0])->toBeInstanceOf(Album::class)
        ->and($response->albums->items[0]->name)->toBe('Kind of Blue');

    Http::assertSent(function ($request): bool {
        if (! str_starts_with($request->url(), 'https://api.spotify.com/v1/search')) {
            return false;
        }

        return $request['q'] === 'remaster track:Doxy artist:Miles Davis'
            && $request['type'] === 'track,album'
            && $request['market'] === 'ES'
            && (string) $request['limit'] === '10'
            && (string) $request['offset'] === '0';
    });
});

it('sends a Bearer token obtained from the accounts endpoint', function (): void {
    Http::fake([
        'api.spotify.com/v1/search*' => Http::response(['tracks' => ['items' => [], 'limit' => 0, 'offset' => 0, 'total' => 0, 'href' => '', 'next' => null, 'previous' => null]]),
    ]);

    Spotify::searchTracks('miles davis')->get();

    Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Bearer fake-access-token'));
});

it('returns a typed Paginated of Track for searchTracks', function (): void {
    Http::fake([
        'api.spotify.com/v1/search*' => Http::response([
            'tracks' => [
                'href' => 'https://api.spotify.com/v1/search?...',
                'items' => [[
                    'id' => 't1',
                    'name' => 'Doxy',
                    'type' => 'track',
                    'duration_ms' => 200000,
                    'explicit' => false,
                    'popularity' => 73,
                    'album' => ['id' => 'a1', 'name' => 'Bag\'s Groove', 'type' => 'album'],
                    'artists' => [['id' => 'ar1', 'name' => 'Miles Davis', 'type' => 'artist']],
                    'external_urls' => ['spotify' => 'https://open.spotify.com/track/t1'],
                ]],
                'limit' => 20,
                'offset' => 0,
                'total' => 1,
                'next' => null,
                'previous' => null,
            ],
        ]),
    ]);

    $tracks = Spotify::searchTracks('Doxy')->get();

    expect($tracks)->toBeInstanceOf(Paginated::class)
        ->and($tracks->total)->toBe(1)
        ->and($tracks->items)->toHaveCount(1)
        ->and($tracks->items[0])->toBeInstanceOf(Track::class)
        ->and($tracks->items[0]->name)->toBe('Doxy')
        ->and($tracks->items[0]->durationMs)->toBe(200000)
        ->and($tracks->items[0]->popularity)->toBe(73)
        ->and($tracks->items[0]->album)->toBeInstanceOf(Album::class)
        ->and($tracks->items[0]->album->name)->toBe('Bag\'s Groove')
        ->and($tracks->items[0]->artists[0])->toBeInstanceOf(Artist::class)
        ->and($tracks->items[0]->artists[0]->name)->toBe('Miles Davis')
        ->and($tracks->items[0]->externalUrls['spotify'])->toBe('https://open.spotify.com/track/t1');
});

it('parses album release dates as Carbon at all three precisions', function (): void {
    Http::fake([
        'api.spotify.com/v1/search*' => Http::response([
            'albums' => [
                'href' => 'h',
                'items' => [
                    ['id' => 'a1', 'name' => 'Day', 'type' => 'album', 'release_date' => '1959-08-17', 'release_date_precision' => 'day'],
                    ['id' => 'a2', 'name' => 'Month', 'type' => 'album', 'release_date' => '1970-04', 'release_date_precision' => 'month'],
                    ['id' => 'a3', 'name' => 'Year', 'type' => 'album', 'release_date' => '1985', 'release_date_precision' => 'year'],
                    ['id' => 'a4', 'name' => 'Missing', 'type' => 'album'],
                ],
                'limit' => 4,
                'offset' => 0,
                'total' => 4,
                'next' => null,
                'previous' => null,
            ],
        ]),
    ]);

    $albums = Spotify::searchAlbums('any')->get();

    [$day, $month, $year, $missing] = $albums->items->all();

    expect($day->releaseDate)->toBeInstanceOf(Carbon::class)
        ->and($day->releaseDate->format('Y-m-d'))->toBe('1959-08-17')
        ->and($month->releaseDate)->toBeInstanceOf(Carbon::class)
        ->and($month->releaseDate->format('Y-m-d'))->toBe('1970-04-01')
        ->and($year->releaseDate)->toBeInstanceOf(Carbon::class)
        ->and($year->releaseDate->format('Y-m-d'))->toBe('1985-01-01')
        ->and($missing->releaseDate)->toBeNull();

    // Round-trip respects precision.
    expect($day->toArray()['release_date'])->toBe('1959-08-17')
        ->and($month->toArray()['release_date'])->toBe('1970-04')
        ->and($year->toArray()['release_date'])->toBe('1985')
        ->and($missing->toArray()['release_date'])->toBeNull();
});

it('hydrates Artist::followers as a typed Followers object', function (): void {
    Http::fake([
        'api.spotify.com/v1/search*' => Http::response([
            'artists' => [
                'href' => 'h',
                'items' => [[
                    'id' => 'ar1',
                    'name' => 'Miles Davis',
                    'type' => 'artist',
                    'genres' => ['jazz', 'bebop'],
                    'popularity' => 71,
                    'followers' => ['href' => null, 'total' => 4_300_500],
                ]],
                'limit' => 1,
                'offset' => 0,
                'total' => 1,
                'next' => null,
                'previous' => null,
            ],
        ]),
    ]);

    $artists = Spotify::searchArtists('miles')->get();
    $artist = $artists->items[0];

    expect($artist)->toBeInstanceOf(Artist::class)
        ->and($artist->followers)->toBeInstanceOf(Followers::class)
        ->and($artist->followers->total)->toBe(4_300_500)
        ->and($artist->followers->href)->toBeNull();

    expect($artist->toArray()['followers'])->toBe(['href' => null, 'total' => 4_300_500]);
});

it('hydrates search playlists with owner, tracks and items links', function (): void {
    Http::fake([
        'api.spotify.com/v1/search*' => Http::response([
            'playlists' => [
                'href' => 'h',
                'items' => [null, [
                    'id' => 'p1',
                    'name' => 'Focus',
                    'type' => 'playlist',
                    'description' => 'deep work',
                    'public' => true,
                    'owner' => [
                        'id' => 'u1',
                        'display_name' => 'spotify',
                        'href' => 'https://api.spotify.com/v1/users/u1',
                        'uri' => 'spotify:user:u1',
                        'type' => 'user',
                        'external_urls' => ['spotify' => 'https://open.spotify.com/user/u1'],
                    ],
                    'tracks' => [
                        'href' => 'https://api.spotify.com/v1/playlists/p1/tracks',
                        'total' => 42,
                    ],
                    'items' => [
                        'href' => 'https://api.spotify.com/v1/playlists/p1/items',
                        'total' => 42,
                    ],
                ]],
                'limit' => 1,
                'offset' => 0,
                'total' => 1,
                'next' => null,
                'previous' => null,
            ],
        ]),
    ]);

    $playlists = Spotify::searchPlaylists('focus')->get();
    $playlist = $playlists->items[0];

    expect($playlists->items)->toHaveCount(1)
        ->and($playlist)->toBeInstanceOf(SimplifiedPlaylist::class)
        ->and($playlist->owner)->toBeInstanceOf(User::class)
        ->and($playlist->owner->id)->toBe('u1')
        ->and($playlist->owner->displayName)->toBe('spotify')
        ->and($playlist->owner->uri)->toBe('spotify:user:u1')
        ->and($playlist->tracks)->toBeInstanceOf(TracksLink::class)
        ->and($playlist->tracks->total)->toBe(42)
        ->and($playlist->tracks->href)->toBe('https://api.spotify.com/v1/playlists/p1/tracks')
        ->and($playlist->items)->toBeInstanceOf(PlaylistItemsLink::class)
        ->and($playlist->items->total)->toBe(42)
        ->and($playlist->items->href)->toBe('https://api.spotify.com/v1/playlists/p1/items');

    $array = $playlist->toArray();

    expect($array['owner'])->toMatchArray([
        'id' => 'u1',
        'display_name' => 'spotify',
        'type' => 'user',
    ])->and($array['tracks'])->toBe([
        'href' => 'https://api.spotify.com/v1/playlists/p1/tracks',
        'total' => 42,
    ])->and($array['items'])->toBe([
        'href' => 'https://api.spotify.com/v1/playlists/p1/items',
        'total' => 42,
    ]);
});

it('gets a playlist with followers and paginated track items', function (): void {
    Http::fake([
        'api.spotify.com/v1/playlists/74oVZlOSwpy31tSplEWONa*' => Http::response([
            'collaborative' => false,
            'description' => 'cinematic moments',
            'external_urls' => ['spotify' => 'https://open.spotify.com/playlist/74oVZlOSwpy31tSplEWONa'],
            'followers' => ['href' => null, 'total' => 24830],
            'href' => 'https://api.spotify.com/v1/playlists/74oVZlOSwpy31tSplEWONa',
            'id' => '74oVZlOSwpy31tSplEWONa',
            'images' => [[
                'height' => null,
                'url' => 'https://image-cdn-ak.spotifycdn.com/image/ab67706c0000da84e2e5f81020090326577c1418',
                'width' => null,
            ]],
            'name' => 'cinematic movie scenes',
            'owner' => [
                'display_name' => 'abby grace',
                'external_urls' => ['spotify' => 'https://open.spotify.com/user/abigailgracee03'],
                'href' => 'https://api.spotify.com/v1/users/abigailgracee03',
                'id' => 'abigailgracee03',
                'type' => 'user',
                'uri' => 'spotify:user:abigailgracee03',
            ],
            'primary_color' => null,
            'public' => true,
            'snapshot_id' => 'AAABEx0gI49LXkQY371hzMaDWTKDUT15',
            'tracks' => [
                'href' => 'https://api.spotify.com/v1/playlists/74oVZlOSwpy31tSplEWONa/tracks?offset=0&limit=100',
                'items' => [[
                    'added_at' => '2024-01-01T00:00:00Z',
                    'added_by' => ['id' => 'abigailgracee03', 'type' => 'user'],
                    'is_local' => false,
                    'primary_color' => null,
                    'track' => [
                        'id' => 't1',
                        'name' => 'Outro',
                        'type' => 'track',
                        'href' => 'https://api.spotify.com/v1/tracks/t1',
                        'uri' => 'spotify:track:t1',
                        'duration_ms' => 252000,
                    ],
                ]],
                'limit' => 100,
                'next' => null,
                'offset' => 0,
                'previous' => null,
                'total' => 1,
            ],
            'type' => 'playlist',
            'uri' => 'spotify:playlist:74oVZlOSwpy31tSplEWONa',
        ]),
    ]);

    $playlist = Spotify::playlist('74oVZlOSwpy31tSplEWONa')->get();

    expect($playlist)->toBeInstanceOf(Playlist::class)
        ->and($playlist->followers)->toBeInstanceOf(Followers::class)
        ->and($playlist->followers->total)->toBe(24830)
        ->and($playlist->tracks)->toBeInstanceOf(TracksLink::class)
        ->and($playlist->tracks->items)->toHaveCount(1)
        ->and($playlist->tracks->items[0])->toBeInstanceOf(PlaylistTrackItem::class)
        ->and($playlist->tracks->items[0]->track)->toBeInstanceOf(Track::class)
        ->and($playlist->tracks->items[0]->track->name)->toBe('Outro')
        ->and($playlist->toArray()['tracks']['items'][0]['track']['name'])->toBe('Outro');
});

it('hydrates Audiobook authors and narrators as typed objects', function (): void {
    Http::fake([
        'api.spotify.com/v1/search*' => Http::response([
            'audiobooks' => [
                'href' => 'h',
                'items' => [[
                    'id' => 'b1',
                    'name' => 'Atomic Habits',
                    'type' => 'audiobook',
                    'publisher' => 'Penguin',
                    'total_chapters' => 20,
                    'authors' => [['name' => 'James Clear']],
                    'narrators' => [['name' => 'James Clear'], ['name' => 'Some Co-narrator']],
                    'languages' => ['en'],
                ]],
                'limit' => 1,
                'offset' => 0,
                'total' => 1,
                'next' => null,
                'previous' => null,
            ],
        ]),
    ]);

    $audiobooks = Spotify::searchAudiobooks('atomic habits')->get();
    $book = $audiobooks->items[0];

    expect($book)->toBeInstanceOf(Audiobook::class)
        ->and($book->authors)->toBeInstanceOf(Collection::class)
        ->and($book->authors)->toHaveCount(1)
        ->and($book->authors[0])->toBeInstanceOf(Author::class)
        ->and($book->authors[0]->name)->toBe('James Clear')
        ->and($book->narrators)->toBeInstanceOf(Collection::class)
        ->and($book->narrators)->toHaveCount(2)
        ->and($book->narrators[0])->toBeInstanceOf(Narrator::class)
        ->and($book->narrators[1]->name)->toBe('Some Co-narrator');

    $array = $book->toArray();

    expect($array['authors'])->toBe([['name' => 'James Clear']])
        ->and($array['narrators'])->toBe([['name' => 'James Clear'], ['name' => 'Some Co-narrator']]);
});

it('exposes the full Laravel Collection API on Paginated::items', function (): void {
    Http::fake([
        'api.spotify.com/v1/search*' => Http::response([
            'tracks' => [
                'href' => 'h',
                'items' => [
                    ['id' => 't1', 'name' => 'A', 'type' => 'track', 'popularity' => 30],
                    ['id' => 't2', 'name' => 'B', 'type' => 'track', 'popularity' => 80],
                    ['id' => 't3', 'name' => 'C', 'type' => 'track', 'popularity' => 60],
                ],
                'limit' => 3,
                'offset' => 0,
                'total' => 3,
                'next' => null,
                'previous' => null,
            ],
        ]),
    ]);

    $tracks = Spotify::searchTracks('any')->get();

    expect($tracks->items)->toBeInstanceOf(Collection::class);

    $popular = $tracks->items
        ->filter(fn (Track $t) => $t->popularity >= 60)
        ->sortByDesc('popularity')
        ->pluck('name')
        ->values()
        ->all();

    expect($popular)->toBe(['B', 'C']);
});

it('exposes Paginated cursor metadata', function (): void {
    Http::fake([
        'api.spotify.com/v1/search*' => Http::response([
            'tracks' => [
                'href' => 'https://api.spotify.com/v1/search?offset=20',
                'items' => [],
                'limit' => 20,
                'offset' => 20,
                'total' => 100,
                'next' => 'https://api.spotify.com/v1/search?offset=40',
                'previous' => 'https://api.spotify.com/v1/search?offset=0',
            ],
        ]),
    ]);

    $tracks = Spotify::searchTracks('miles')->offset(20)->get();

    expect($tracks->offset)->toBe(20)
        ->and($tracks->limit)->toBe(20)
        ->and($tracks->total)->toBe(100)
        ->and($tracks->next)->toBe('https://api.spotify.com/v1/search?offset=40')
        ->and($tracks->previous)->toBe('https://api.spotify.com/v1/search?offset=0');
});

it('appends include_external=audio when requested', function (): void {
    Http::fake([
        'api.spotify.com/v1/search*' => Http::response(['episodes' => ['items' => [], 'limit' => 0, 'offset' => 0, 'total' => 0, 'href' => '', 'next' => null, 'previous' => null]]),
    ]);

    Spotify::searchEpisodes('podcast')->includeExternalAudio()->get();

    Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://api.spotify.com/v1/search')
        && ($request['include_external'] ?? null) === 'audio');
});

it('serializes typed resources back to arrays via toArray', function (): void {
    Http::fake([
        'api.spotify.com/v1/search*' => Http::response([
            'tracks' => [
                'href' => 'h',
                'items' => [['id' => 't1', 'name' => 'Doxy', 'type' => 'track']],
                'limit' => 1,
                'offset' => 0,
                'total' => 1,
                'next' => null,
                'previous' => null,
            ],
        ]),
    ]);

    $tracks = Spotify::searchTracks('Doxy')->get();
    $array = $tracks->toArray();

    expect($array)->toHaveKey('items')
        ->and($array['items'][0])->toHaveKey('name')
        ->and($array['items'][0]['name'])->toBe('Doxy');
});

it('rejects searches without a query', function (): void {
    Spotify::search('', [SearchType::Track])->get();
})->throws(ValidationException::class);

it('rejects multi-type searches with no types', function (): void {
    Spotify::search('Doxy')->get();
})->throws(ValidationException::class);

it('translates 401 responses into AuthenticationException after a token refresh retry', function (): void {
    Http::fake([
        'api.spotify.com/v1/search*' => Http::response(['error' => ['status' => 401, 'message' => 'expired']], 401),
    ]);

    Spotify::searchTracks('foo')->get();
})->throws(AuthenticationException::class);

it('translates 429 responses into RateLimitException with Retry-After', function (): void {
    Http::fake([
        'api.spotify.com/v1/search*' => Http::response(
            ['error' => ['status' => 429, 'message' => 'slow down']],
            429,
            ['Retry-After' => '7'],
        ),
    ]);

    try {
        Spotify::searchTracks('foo')->get();
        expect(false)->toBeTrue('expected RateLimitException');
    } catch (RateLimitException $e) {
        expect($e->retryAfter)->toBe(7)
            ->and($e->getMessage())->toBe('slow down');
    }
});

it('translates other 4xx/5xx responses into a generic ApiException', function (): void {
    Http::fake([
        'api.spotify.com/v1/search*' => Http::response(
            ['error' => ['status' => 500, 'message' => 'boom']],
            500,
        ),
    ]);

    try {
        Spotify::searchTracks('foo')->get();
        expect(false)->toBeTrue('expected ApiException');
    } catch (ApiException $e) {
        expect($e->getCode())->toBe(500)
            ->and($e->getMessage())->toBe('boom');
    }
});

it('caches the access token across multiple requests', function (): void {
    Http::fake([
        'api.spotify.com/v1/search*' => Http::response(['tracks' => ['items' => [], 'limit' => 0, 'offset' => 0, 'total' => 0, 'href' => '', 'next' => null, 'previous' => null]]),
    ]);

    Spotify::searchTracks('one')->get();
    Spotify::searchTracks('two')->get();

    $tokenRequests = collect(Http::recorded())
        ->filter(fn ($pair): bool => str_contains($pair[0]->url(), 'accounts.spotify.com/api/token'))
        ->count();

    expect($tokenRequests)->toBe(1);
});

it('accepts string types and coerces them to SearchType enums', function (): void {
    Http::fake([
        'api.spotify.com/v1/search*' => Http::response(['tracks' => ['items' => [], 'limit' => 0, 'offset' => 0, 'total' => 0, 'href' => '', 'next' => null, 'previous' => null]]),
    ]);

    Spotify::search('miles', ['track', 'album'])->get();

    Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://api.spotify.com/v1/search')
        && ($request['type'] ?? null) === 'track,album');
});
