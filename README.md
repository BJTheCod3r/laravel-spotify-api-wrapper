# Laravel Spotify API Wrapper

A Laravel wrapper for the [Spotify Web API](https://developer.spotify.com/documentation/web-api), built around the **Action pattern** with a fluent facade.

The first focus is the [Search API](https://developer.spotify.com/documentation/web-api/reference/search), but the package is structured so adding the rest of the surface area (Albums, Artists, Browse, Playlists, Player, Users, …) is just adding more action classes.

## Requirements

- PHP `^8.2`
- Laravel `^11.0` or `^12.0`

## Installation

```bash
composer require bjthecod3r/laravel-spotify-api-wrapper
```

Publish the config file:

```bash
php artisan vendor:publish --tag=spotify-config
```

## Configuration

Add your [Spotify app](https://developer.spotify.com/dashboard) credentials to `.env`:

```dotenv
SPOTIFY_CLIENT_ID=your-client-id
SPOTIFY_CLIENT_SECRET=your-client-secret

# Optional defaults
SPOTIFY_MARKET=US
SPOTIFY_LOCALE=en_US
SPOTIFY_CACHE_STORE=redis
```

The package uses the **Client Credentials** grant. Tokens are cached for the duration Spotify reports in `expires_in` (minus a safety buffer), so subsequent requests reuse the same token.

## Usage

### Multi-type search

```php
use BjTheCod3r\Spotify\Enums\SearchType;
use BjTheCod3r\Spotify\Facades\Spotify;

$results = Spotify::search('remaster track:Doxy artist:Miles Davis', [
        SearchType::Track,
        SearchType::Album,
    ])
    ->market('ES')
    ->limit(10)
    ->offset(0)
    ->get();

// $results is a typed SearchResults object.
$results->tracks->items[0]->name;          // string — Track::$name
$results->tracks->items[0]->album->name;   // nested Album
$results->albums->items[0]->releaseDate;   // string|null
$results->albums->total;                   // int — paging total
$results->artists;                         // null — wasn't requested
```

You can also pass type strings if you prefer:

```php
Spotify::search('miles davis', ['track', 'album'])->get();
```

### Single-type search (returns a typed `Paginated`)

```php
$tracks    = Spotify::searchTracks('Doxy')->limit(20)->get();   // Paginated<Track>
$albums    = Spotify::searchAlbums('Kind of Blue')->get();      // Paginated<Album>
$artists   = Spotify::searchArtists('Miles Davis')->get();      // Paginated<Artist>
$playlists = Spotify::searchPlaylists('focus')->get();          // Paginated<Playlist>
$shows     = Spotify::searchShows('how i built this')->get();   // Paginated<Show>
$episodes  = Spotify::searchEpisodes('startups')->get();        // Paginated<Episode>
$audiobooks = Spotify::searchAudiobooks('atomic habits')->get(); // Paginated<Audiobook>

foreach ($tracks->items as $track) {
    echo $track->name.' — '.$track->artists[0]->name.PHP_EOL;
}

$tracks->next;     // string|null — URL for the next page
$tracks->total;    // int
```

### Typed resources

Every search response is hydrated into resource objects under `BjTheCod3r\Spotify\Resources\`:

| Resource       | Notable fields                                                                |
|----------------|-------------------------------------------------------------------------------|
| `Track`        | `name`, `durationMs`, `explicit`, `popularity`, `previewUrl`, `album`, `artists` |
| `Album`        | `name`, `albumType`, `totalTracks`, `releaseDate`, `images`, `artists`        |
| `Artist`       | `name`, `genres`, `popularity`, `images`, `followersTotal`                    |
| `Playlist`     | `name`, `description`, `public`, `ownerDisplayName`, `tracksTotal`, `images`  |
| `Show`         | `name`, `description`, `publisher`, `totalEpisodes`, `images`                 |
| `Episode`      | `name`, `description`, `durationMs`, `releaseDate`, `audioPreviewUrl`         |
| `Audiobook`    | `name`, `description`, `authors`, `narrators`, `publisher`, `totalChapters`   |
| `Image`        | `url`, `height`, `width`                                                      |
| `Paginated<T>` | `items`, `total`, `limit`, `offset`, `next`, `previous`, `href`               |

All resources implement `Arrayable` + `JsonSerializable`, so you can return them directly from a controller (`return $tracks;`) and Laravel will serialize them to JSON.

### Field filters

Spotify supports inline filters in the query string itself. Just pass them through:

```php
Spotify::searchTracks('artist:Burna Boy year:2022')->get();
Spotify::searchAlbums('tag:new')->get();
Spotify::searchTracks('isrc:USAT22003158')->get();
```

### Action pattern (without the facade)

Every entry point on the facade is a thin wrapper around an Action class.
You can resolve and invoke actions directly — useful for queueing them or
invoking them from Form Requests, Jobs, etc.:

```php
use BjTheCod3r\Spotify\Actions\Search\SearchTracksAction;

$tracks = app(SearchTracksAction::class)
    ->q('Doxy')
    ->market('NG')
    ->limit(15)
    ->execute();
```

Each action is a single, testable unit of work that:
- accepts only the parameters Spotify documents for that endpoint,
- exposes them via fluent setters,
- validates required inputs,
- and returns the decoded JSON payload.

## Error handling

| Status | Exception                                            |
|--------|------------------------------------------------------|
| 400/422 | `BjTheCod3r\Spotify\Exceptions\ValidationException` |
| 401    | `BjTheCod3r\Spotify\Exceptions\AuthenticationException` (after a single transparent token refresh + retry) |
| 429    | `BjTheCod3r\Spotify\Exceptions\RateLimitException` (exposes `retryAfter` in seconds) |
| Other  | `BjTheCod3r\Spotify\Exceptions\ApiException`         |

All inherit from `BjTheCod3r\Spotify\Exceptions\SpotifyException`, so you can catch broadly when you don't care about the specific cause.

## Testing

The package ships with Pest + Orchestra Testbench:

```bash
composer install
composer test
```

In your own application's tests, fake the HTTP layer with Laravel's standard helpers:

```php
Http::fake([
    'accounts.spotify.com/*' => Http::response(['access_token' => 'x', 'token_type' => 'Bearer', 'expires_in' => 3600]),
    'api.spotify.com/v1/search*' => Http::response(['tracks' => ['items' => []]]),
]);
```

## Roadmap

The package is built with the full Spotify Web API surface in mind. Adding a new endpoint group is mostly:

1. Drop a new directory under `src/Actions/` (e.g. `Albums/`).
2. Add an `XxxAction` class extending `BaseAction`, with `path()` + `parameters()`.
3. Add an entry-point method on `Spotify.php` that returns it.
4. Add a `@method` annotation to the facade docblock.

Planned coverage:

- [x] Search
- [ ] Albums (`/albums`, `/albums/{id}`, `/albums/{id}/tracks`)
- [ ] Artists (`/artists`, `/artists/{id}`, `/artists/{id}/albums`, `/artists/{id}/top-tracks`, `/artists/{id}/related-artists`)
- [ ] Tracks (`/tracks`, `/tracks/{id}`, `/audio-features`, `/audio-analysis`)
- [ ] Episodes & Shows
- [ ] Audiobooks & Chapters
- [ ] Browse (`/browse/categories`, `/browse/new-releases`, `/browse/featured-playlists`)
- [ ] Playlists (read-only first; mutations need user-context auth)
- [ ] Markets, Genres
- [ ] Users (requires Authorization Code / PKCE — separate auth flow)
- [ ] Player (requires user-context auth)

## License

MIT.
