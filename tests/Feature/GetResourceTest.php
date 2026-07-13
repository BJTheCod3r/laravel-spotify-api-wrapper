<?php

declare(strict_types=1);

use BjTheCod3r\Spotify\Facades\Spotify;
use BjTheCod3r\Spotify\Resources\Album;
use BjTheCod3r\Spotify\Resources\Artist;
use BjTheCod3r\Spotify\Resources\Audiobook;
use BjTheCod3r\Spotify\Resources\Episode;
use BjTheCod3r\Spotify\Resources\Followers;
use BjTheCod3r\Spotify\Resources\Paginated;
use BjTheCod3r\Spotify\Resources\Show;
use BjTheCod3r\Spotify\Resources\Track;
use BjTheCod3r\Spotify\Resources\User;
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

it('gets an album with market query', function (): void {
    Http::fake([
        'api.spotify.com/v1/albums/4aawyAB9vmqN3uQ7FjRGTy*' => Http::response([
            'id' => '4aawyAB9vmqN3uQ7FjRGTy',
            'name' => 'Global Warming',
            'type' => 'album',
            'album_type' => 'album',
            'total_tracks' => 18,
            'release_date' => '2012-11-19',
            'release_date_precision' => 'day',
            'artists' => [['id' => 'ar1', 'name' => 'Pitbull', 'type' => 'artist']],
        ]),
    ]);

    $album = Spotify::album('4aawyAB9vmqN3uQ7FjRGTy')->market('US')->get();

    expect($album)->toBeInstanceOf(Album::class)
        ->and($album->name)->toBe('Global Warming')
        ->and($album->totalTracks)->toBe(18);

    Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://api.spotify.com/v1/albums/4aawyAB9vmqN3uQ7FjRGTy')
        && ($request['market'] ?? null) === 'US');
});

it('gets an album\'s tracks paginated with market and paging queries', function (): void {
    Http::fake([
        'api.spotify.com/v1/albums/4aawyAB9vmqN3uQ7FjRGTy/tracks*' => Http::response([
            'href' => 'https://api.spotify.com/v1/albums/4aawyAB9vmqN3uQ7FjRGTy/tracks?offset=0&limit=2',
            'limit' => 2,
            'offset' => 0,
            'total' => 18,
            'next' => 'https://api.spotify.com/v1/albums/4aawyAB9vmqN3uQ7FjRGTy/tracks?offset=2&limit=2',
            'previous' => null,
            'items' => [
                [
                    'id' => 'tr1',
                    'name' => 'Global Warming',
                    'type' => 'track',
                    'track_number' => 1,
                    'disc_number' => 1,
                    'duration_ms' => 89_000,
                    'artists' => [['id' => 'ar1', 'name' => 'Pitbull', 'type' => 'artist']],
                ],
                [
                    'id' => 'tr2',
                    'name' => 'Feel This Moment',
                    'type' => 'track',
                    'track_number' => 2,
                    'disc_number' => 1,
                    'duration_ms' => 229_000,
                    'artists' => [['id' => 'ar1', 'name' => 'Pitbull', 'type' => 'artist']],
                ],
            ],
        ]),
    ]);

    $tracks = Spotify::albumTracks('4aawyAB9vmqN3uQ7FjRGTy')
        ->market('US')
        ->limit(2)
        ->offset(0)
        ->get();

    expect($tracks)->toBeInstanceOf(Paginated::class)
        ->and($tracks->total)->toBe(18)
        ->and($tracks->items)->toHaveCount(2)
        ->and($tracks->items->first())->toBeInstanceOf(Track::class)
        ->and($tracks->items->first()->name)->toBe('Global Warming')
        ->and($tracks->items->first()->trackNumber)->toBe(1)
        ->and($tracks->items->first()->album)->toBeNull();

    Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://api.spotify.com/v1/albums/4aawyAB9vmqN3uQ7FjRGTy/tracks')
        && ($request['market'] ?? null) === 'US'
        && (string) $request['limit'] === '2'
        && (string) $request['offset'] === '0');
});

it('gets an artist with followers', function (): void {
    Http::fake([
        'api.spotify.com/v1/artists/0TnOYISbd1XYRBk9myaseg*' => Http::response([
            'id' => '0TnOYISbd1XYRBk9myaseg',
            'name' => 'Pitbull',
            'type' => 'artist',
            'genres' => ['dance pop', 'miami hip hop'],
            'popularity' => 81,
            'followers' => ['href' => null, 'total' => 25_000_000],
        ]),
    ]);

    $artist = Spotify::artist('0TnOYISbd1XYRBk9myaseg')->get();

    expect($artist)->toBeInstanceOf(Artist::class)
        ->and($artist->name)->toBe('Pitbull')
        ->and($artist->followers)->toBeInstanceOf(Followers::class)
        ->and($artist->followers->total)->toBe(25_000_000);
});

it('gets a track with market query', function (): void {
    Http::fake([
        'api.spotify.com/v1/tracks/11dFghVXANMlKmJXsNCbNl*' => Http::response([
            'id' => '11dFghVXANMlKmJXsNCbNl',
            'name' => 'Cut to the Feeling',
            'type' => 'track',
            'duration_ms' => 207959,
            'explicit' => false,
            'popularity' => 65,
        ]),
    ]);

    $track = Spotify::track('11dFghVXANMlKmJXsNCbNl')->market('US')->get();

    expect($track)->toBeInstanceOf(Track::class)
        ->and($track->name)->toBe('Cut to the Feeling')
        ->and($track->durationMs)->toBe(207959);

    Http::assertSent(fn ($request): bool => ($request['market'] ?? null) === 'US');
});

it('gets a show with market query', function (): void {
    Http::fake([
        'api.spotify.com/v1/shows/38bS44xjbVVZ3No3ByF1dJ*' => Http::response([
            'id' => '38bS44xjbVVZ3No3ByF1dJ',
            'name' => 'Reply All',
            'type' => 'show',
            'publisher' => 'Gimlet',
            'total_episodes' => 192,
        ]),
    ]);

    $show = Spotify::show('38bS44xjbVVZ3No3ByF1dJ')->market('US')->get();

    expect($show)->toBeInstanceOf(Show::class)
        ->and($show->name)->toBe('Reply All')
        ->and($show->totalEpisodes)->toBe(192);
});

it('gets an episode with market query', function (): void {
    Http::fake([
        'api.spotify.com/v1/episodes/512ojhOuo1ktJprKbVcKyQ*' => Http::response([
            'id' => '512ojhOuo1ktJprKbVcKyQ',
            'name' => '#127 The Crime Machine',
            'type' => 'episode',
            'duration_ms' => 3_600_000,
            'release_date' => '2018-10-12',
            'release_date_precision' => 'day',
        ]),
    ]);

    $episode = Spotify::episode('512ojhOuo1ktJprKbVcKyQ')->market('US')->get();

    expect($episode)->toBeInstanceOf(Episode::class)
        ->and($episode->name)->toBe('#127 The Crime Machine');
});

it('gets an audiobook with market query', function (): void {
    Http::fake([
        'api.spotify.com/v1/audiobooks/7iHfbu1YPACw6oZPAFJtqe*' => Http::response([
            'id' => '7iHfbu1YPACw6oZPAFJtqe',
            'name' => 'Atomic Habits',
            'type' => 'audiobook',
            'publisher' => 'Penguin',
            'total_chapters' => 20,
            'authors' => [['name' => 'James Clear']],
            'narrators' => [['name' => 'James Clear']],
            'languages' => ['en'],
        ]),
    ]);

    $book = Spotify::audiobook('7iHfbu1YPACw6oZPAFJtqe')->market('US')->get();

    expect($book)->toBeInstanceOf(Audiobook::class)
        ->and($book->name)->toBe('Atomic Habits')
        ->and($book->authors[0]->name)->toBe('James Clear');
});

it('gets a user profile', function (): void {
    Http::fake([
        'api.spotify.com/v1/users/smedjan' => Http::response([
            'id' => 'smedjan',
            'display_name' => 'JM Wizard',
            'type' => 'user',
            'href' => 'https://api.spotify.com/v1/users/smedjan',
            'uri' => 'spotify:user:smedjan',
            'followers' => ['href' => null, 'total' => 3650],
        ]),
    ]);

    $user = Spotify::user('smedjan')->get();

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->id)->toBe('smedjan')
        ->and($user->displayName)->toBe('JM Wizard')
        ->and($user->followers?->total)->toBe(3650);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.spotify.com/v1/users/smedjan');
});
