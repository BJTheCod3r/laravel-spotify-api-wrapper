# Laravel Spotify API Wrapper

A Laravel wrapper for the [Spotify Web API](https://developer.spotify.com/documentation/web-api). Search across tracks, albums, artists, playlists, shows, episodes, and audiobooks with a fluent facade and fully-typed responses.

## Highlights

- **Fluent search** for every Spotify item type, with Spotify's full filter syntax (`artist:`, `year:`, `tag:new`, `isrc:`, …) supported out of the box.
- **Typed responses.** No more reaching into nested arrays — every response is hydrated into PHP objects with public typed properties (`$track->album->name`, `$album->releaseDate` is a `Carbon` instance, etc.).
- **Pagination built in.** `Paginated` exposes `items`, `total`, `limit`, `offset`, `next`, and `previous` so you can page or drive a "Load more" button without parsing URLs.
- **Auth handled for you.** Client-credentials tokens are fetched, cached for the duration Spotify reports, and transparently refreshed on a 401.
- **Typed exceptions** mapped from Spotify's status codes — catch `RateLimitException` to read `retryAfter`, `AuthenticationException` for credential issues, etc.
- **Drop-in JSON.** Resources implement `Arrayable` + `JsonSerializable`, so `return $results;` from a controller serializes correctly.

## Requirements

- PHP `^8.2`
- Laravel `^11.0`, `^12.0`, or `^13.0`

## Installation

```bash
composer require bjthecod3r/laravel-spotify-api-wrapper
```

Publish the config:

```bash
php artisan vendor:publish --tag=spotify-config
```

Add your [Spotify app](https://developer.spotify.com/dashboard) credentials to `.env`:

```dotenv
SPOTIFY_CLIENT_ID=your-client-id
SPOTIFY_CLIENT_SECRET=your-client-secret

# Optional defaults
SPOTIFY_MARKET=US
SPOTIFY_LOCALE=en_US
SPOTIFY_CACHE_STORE=redis
```

## Search

### Single-type search

The most common case — search one type, get a typed `Paginated` back:

```php
use BjTheCod3r\Spotify\Facades\Spotify;

$tracks    = Spotify::searchTracks('Doxy')->limit(20)->get();
$albums    = Spotify::searchAlbums('Kind of Blue')->market('NG')->get();
$artists   = Spotify::searchArtists('Miles Davis')->get();
$playlists = Spotify::searchPlaylists('focus')->get();
$shows     = Spotify::searchShows('how i built this')->get();
$episodes  = Spotify::searchEpisodes('startups')->includeExternalAudio()->get();
$audiobooks = Spotify::searchAudiobooks('atomic habits')->get();

foreach ($tracks->items as $track) {
    echo $track->name.' — '.$track->artists[0]->name.PHP_EOL;
}

$tracks->total;     // int
$tracks->next;      // ?string — URL for the next page
$tracks->previous;  // ?string
```

### Get a playlist

```php
$playlist = Spotify::playlist('74oVZlOSwpy31tSplEWONa')
    ->market('GB')
    ->get();

$playlist->followers->total;
$playlist->tracks->items[0]->track->name;
```

Search playlists hydrate as `SimplifiedPlaylist` summaries. Direct playlist lookups hydrate as
`Playlist` so `followers` and paginated `tracks.items` are only present on the
endpoint that returns them.

### Multi-type search

When you want several item types in one request:

```php
use BjTheCod3r\Spotify\Enums\SearchType;

$results = Spotify::search('remaster track:Doxy artist:Miles Davis', [
        SearchType::Track,
        SearchType::Album,
    ])
    ->market('ES')
    ->limit(10)
    ->get();

$results->tracks->items[0]->name;          // Track::$name
$results->tracks->items[0]->album->name;   // nested Album
$results->albums->total;                   // paging total
$results->artists;                         // null — wasn't requested
```

Type strings work too if you'd rather skip the enum import:

```php
Spotify::search('miles davis', ['track', 'album'])->get();
```

### Field filters

Spotify supports inline filters in the query string. Just pass them through:

```php
Spotify::searchTracks('artist:Burna Boy year:2022')->get();
Spotify::searchAlbums('tag:new')->get();
Spotify::searchTracks('isrc:USAT22003158')->get();
```

### Pagination

```php
$page = Spotify::searchTracks('miles')->limit(20)->offset(0)->get();

$page->items;     // array<Track>
$page->total;     // 8462
$page->offset;    // 0
$page->next;      // 'https://api.spotify.com/v1/search?...&offset=20'
```

## Typed resources

Every search response hydrates into objects under `BjTheCod3r\Spotify\Resources\`:

| Resource       | Notable fields                                                                  |
|----------------|---------------------------------------------------------------------------------|
| `Track`        | `name`, `durationMs`, `explicit`, `popularity`, `previewUrl`, `album`, `artists` |
| `Album`        | `name`, `albumType`, `totalTracks`, `releaseDate` (Carbon), `images`, `artists` |
| `Artist`       | `name`, `genres`, `popularity`, `images`, `followers` (`Followers` — `href`, `total`) |
| `SimplifiedPlaylist` | `name`, `description`, `public`, `owner` (`User`), `tracks` (`TracksLink` — `href`, `total`), `items` (`PlaylistItemsLink`), `images` |
| `Playlist`     | `name`, `description`, `public`, `followers`, `owner` (`User`), `tracks` (`TracksLink` — `href`, `total`, `items`), `images` |
| `Show`         | `name`, `description`, `publisher`, `totalEpisodes`, `images`                   |
| `Episode`      | `name`, `description`, `durationMs`, `releaseDate` (Carbon), `audioPreviewUrl` |
| `Audiobook`    | `name`, `description`, `authors` (`Author[]`), `narrators` (`Narrator[]`), `publisher`, `totalChapters` |
| `Image`        | `url`, `height`, `width`                                                        |
| `Paginated<T>` | `items`, `total`, `limit`, `offset`, `next`, `previous`, `href`                 |

Date fields are real `Illuminate\Support\Carbon` instances. Spotify's date precision (`year`, `month`, `day`) is preserved on round-trip via `releaseDatePrecision`.

List fields (`items`, `artists`, `images`, `genres`, `languages`, `authors`, `narrators`, …) are `Illuminate\Support\Collection` instances, so you get the full Laravel Collection API:

```php
$tracks->items
    ->filter(fn (Track $t) => $t->popularity > 50)
    ->sortByDesc('popularity')
    ->map(fn (Track $t) => $t->name);

$artist->genres->contains('jazz');
$album->artists->pluck('name');
```

Resources implement `Arrayable` + `JsonSerializable`, so this works:

```php
public function index()
{
    return Spotify::searchTracks(request('q'))->get();
}
```

Laravel will serialize the `Paginated<Track>` to JSON automatically.

## Error handling

| Status     | Exception                                                                                                  |
|------------|------------------------------------------------------------------------------------------------------------|
| 400 / 422  | `BjTheCod3r\Spotify\Exceptions\ValidationException`                                                        |
| 401        | `BjTheCod3r\Spotify\Exceptions\AuthenticationException` (after a transparent token refresh + retry)        |
| 429        | `BjTheCod3r\Spotify\Exceptions\RateLimitException` — exposes `retryAfter` in seconds                       |
| Other 4xx/5xx | `BjTheCod3r\Spotify\Exceptions\ApiException`                                                            |

All inherit from `BjTheCod3r\Spotify\Exceptions\SpotifyException`, so you can catch broadly:

```php
try {
    $tracks = Spotify::searchTracks($q)->get();
} catch (RateLimitException $e) {
    return response('Slow down', 429)->header('Retry-After', (string) $e->retryAfter);
} catch (SpotifyException $e) {
    report($e);
    return back()->with('error', 'Spotify is having a moment. Try again.');
}
```

## Authentication

The package uses Spotify's **Client Credentials** grant — no user login required, suitable for any endpoint that doesn't need user context (Search, Browse, Albums, Artists, Tracks). Tokens are cached using Laravel's cache for the duration Spotify reports in `expires_in`, minus a small safety buffer, so you only hit the auth endpoint when a token actually needs refreshing.

User-context flows (Authorization Code / PKCE) for Playlists, Player, and User endpoints will arrive in a later release.

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

- [x] Search
- [ ] Albums
- [ ] Artists
- [ ] Tracks (incl. audio features / analysis)
- [ ] Episodes & Shows
- [ ] Audiobooks & Chapters
- [ ] Browse (categories, new releases, featured playlists)
- [ ] Playlists (read-only first)
- [ ] Markets, Genres
- [ ] Users — requires Authorization Code / PKCE
- [ ] Player — requires user-context auth

## License

MIT.
