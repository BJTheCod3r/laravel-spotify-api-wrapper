<p align="center">
    <img src="art/logo.svg" alt="Laravel Spotify API Wrapper" width="380">
</p>

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

The lookup above carries only the first page of items inline. Page past it with
`playlistItems()`, which returns a `Paginated` of `PlaylistTrackItem`:

```php
$items = Spotify::playlistItems('74oVZlOSwpy31tSplEWONa')
    ->limit(50)
    ->offset(50)
    ->get();

$items->items->first()->track->name;
```

That call uses app-level credentials, which only reach public playlists. For a
listener's private or collaborative ones, go through `asUser()` with the
`playlist-read-private` scope.

To create or edit playlists, see [Creating and editing playlists](#creating-and-editing-playlists).
Those endpoints act on a listener's account and need user authentication.

### Get a single resource by ID

Direct lookups exist for every searchable resource, plus user profiles. They all
return a fully-typed resource (the same classes the search endpoints hydrate),
and accept `->market()` where Spotify supports it.

```php
$album     = Spotify::album('4aawyAB9vmqN3uQ7FjRGTy')->market('US')->get();
$artist    = Spotify::artist('0TnOYISbd1XYRBk9myaseg')->get();
$track     = Spotify::track('11dFghVXANMlKmJXsNCbNl')->market('US')->get();
$show      = Spotify::show('38bS44xjbVVZ3No3ByF1dJ')->market('US')->get();
$episode   = Spotify::episode('512ojhOuo1ktJprKbVcKyQ')->market('US')->get();
$audiobook = Spotify::audiobook('7iHfbu1YPACw6oZPAFJtqe')->market('US')->get();
$user      = Spotify::user('smedjan')->get();
```

`Album`, `Track`, `Show`, and `Audiobook` expose the catalogue's country list as
`availableMarkets` (`Collection<string>` of ISO 3166-1 alpha-2 codes). Sending a
`market` empties it — Spotify swaps the list for `is_playable` — so omit
`->market()` when you want the countries. That applies to the search endpoints and
`artistTopTracks()` too, which seed `spotify.defaults.market`. `Episode` has no
`availableMarkets` at all; Spotify returns the field only on its nested show.

```php
$album = Spotify::album('4aawyAB9vmqN3uQ7FjRGTy')->get();

$album->availableMarkets->contains('NG');  // true
```

### An album's tracks

`Spotify::albumTracks($id)` pages through an album's track list. It returns the
same `Paginated` resource as search, with each item hydrated as a `Track`.
Accepts `->market()`, `->limit()`, and `->offset()`.

```php
$page = Spotify::albumTracks('4aawyAB9vmqN3uQ7FjRGTy')
    ->market('US')
    ->limit(20)
    ->offset(0)
    ->get();

$page->items;                 // Collection<Track>
$page->total;                 // 18
$page->items->first()->name;  // 'Global Warming'
$page->next;                  // next-page URL, or null on the last page
```

These are Spotify's *simplified* tracks, so `album`, `popularity`, and
`externalIds` come back null — fetch `Spotify::track($id)` for the full object.

### An artist's top tracks

`Spotify::artistTopTracks($id)` returns the artist's most popular tracks (up to
10) for a market. Spotify sends a bare list here rather than a paging object, so
you get a `Collection<Track>` back — only `->market()` applies.

```php
$tracks = Spotify::artistTopTracks('0TnOYISbd1XYRBk9myaseg')
    ->market('US')
    ->get();

$tracks->count();                // 10
$tracks->first()->name;          // 'Give Me Everything'
$tracks->first()->album->name;   // 'Planet Pit'
```

`market` is optional to the API, but Spotify only infers one from a *user* token
(`Spotify::asUser($id)->artistTopTracks(...)`, where the account's country wins
over anything you pass). On the default client-credentials token there is no
country to fall back on, and Spotify then treats the catalogue as unavailable
and returns an empty list.

Because of that, this is the one Get-by-ID action that seeds
`spotify.defaults.market` (`SPOTIFY_MARKET`) — set it once and top-track calls
work without an explicit market. `->market()` still overrides it per call. The
other Get-by-ID endpoints deliberately leave `market` unset, since there a
market *narrows* the response rather than enabling it.

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

## User authentication

For endpoints that act on a listener's account — their playlists, library, top items, listening history — connect their Spotify account via the **Authorization Code + PKCE** flow.

> **About playback.** The Spotify Web API does not return playable audio URLs even for authenticated users. Full playback is gated to Spotify's Web Playback SDK (browser, Premium) and the mobile SDKs. The user-auth surface here is for *reading* user data and (in a future release) *controlling* an active device, not for direct streaming.

### Setup

Publish the migration that stores per-user tokens, then run it:

```bash
php artisan vendor:publish --tag=spotify-migrations
php artisan migrate
```

Tokens are encrypted at rest using Laravel's app key.

Set the OAuth redirect URI on your `.env` (and register the same value on the Spotify dashboard for your app):

```dotenv
SPOTIFY_REDIRECT_URI=https://your-app.test/spotify/callback
```

The package registers three opt-in routes under the `spotify` prefix (configurable):

| Method | URI                  | Name                  |
|--------|----------------------|-----------------------|
| GET    | `/spotify/connect`   | `spotify.connect`     |
| GET    | `/spotify/callback`  | `spotify.callback`    |
| POST   | `/spotify/disconnect`| `spotify.disconnect`  |

Set `spotify.oauth.routes.enabled` to `false` (or env `SPOTIFY_OAUTH_ROUTES_ENABLED=false`) to disable them and wire your own controllers using the `Spotify::redirect()` / `Spotify::handleCallback()` helpers.

### Connecting a user

Have the authenticated user hit the `connect` route — by default it requires the `web` + `auth` middleware:

```blade
<a href="{{ route('spotify.connect') }}">Connect Spotify</a>
```

Pass extra scopes via `?scopes=playlist-modify-public,user-modify-playback-state` to merge with the configured defaults.

After consent, Spotify redirects to `/spotify/callback`. The controller exchanges the code, captures the listener's Spotify user id, persists encrypted tokens, dispatches `SpotifyConnected`, and redirects to `oauth.after_connect`.

If anything fails (state mismatch, user denied consent on Spotify, exchange error, …), the callback still redirects to `oauth.after_connect` but flashes a `spotify.oauth.error` payload onto the session so the destination can render error UX:

```blade
@if ($error = session('spotify.oauth.error'))
    <div class="alert">
        Spotify connect failed: {{ $error['reason'] }}
        @if ($error['description']) ({{ $error['description'] }}) @endif
    </div>
@endif
```

The `reason` is one of `state_mismatch`, `user_denied`, `authorize_error`, or `exchange_failed`; `description` carries the underlying Spotify error code or exception message.

### Reading user data

```php
use BjTheCod3r\Spotify\Facades\Spotify;

// Implicit: resolves the current user via the configured guard.
$profile      = Spotify::me()->profile()->get();
$playlists    = Spotify::me()->playlists()->limit(50)->get();
$savedTracks  = Spotify::me()->savedTracks()->market('US')->limit(50)->get();
$savedAlbums  = Spotify::me()->savedAlbums()->get();
$topTracks    = Spotify::me()->topTracks()->timeRange('short_term')->get();
$topArtists   = Spotify::me()->topArtists()->get();
$recent       = Spotify::me()->recentlyPlayed()->limit(50)->get();
$following    = Spotify::me()->followedArtists()->limit(50)->get();

// Explicit: act as a specific user id (queue workers, jobs).
Spotify::asUser($userId)->me()->playlists()->get();
```

All `me()` endpoints that return collections come back as the same `Paginated` resource the rest of the package uses. The `me/tracks`, `me/albums`, `me/shows`, `me/episodes`, and `me/player/recently-played` envelopes are unwrapped — the items collection contains the inner `Track` / `Album` / etc. directly. The `added_at` / `played_at` timestamps from those envelopes are not exposed in v0.3.

### Creating and editing playlists

Playlist writes act on the connected listener's account, so they need the
modify scopes. They aren't in the defaults, so request them at connect time:

```blade
<a href="{{ route('spotify.connect') }}?scopes=playlist-modify-public,playlist-modify-private">
    Connect Spotify
</a>
```

`playlist-modify-public` covers public playlists, `playlist-modify-private`
covers private and collaborative ones. Spotify answers `403` when the granted
scopes don't cover the playlist you're writing to.

```php
// Create. The owner defaults to the connected account.
$playlist = Spotify::createPlaylist('Top 100 of 2026')
    ->description('Generated from my ratings')
    ->public(false)
    ->get();

// Add items. Bare ids and open.spotify.com links are normalised to URIs;
// a bare id is assumed to be a track, so pass episodes as full URIs.
// Input matching none of those three forms throws ValidationException.
$snapshot = Spotify::addPlaylistItems($playlist->id, [
    '4iV5W9uYEdYUVa79Axb7Rh',
    'spotify:track:1301WleyT98MSxVHPZCA6M',
    'https://open.spotify.com/track/7ouMYWpwJ422jRcDASZB7P',
])->get();

// Insert at the top instead of appending.
Spotify::addPlaylistItems($playlist->id, $uris)->position(0)->get();

// Rename / re-describe / flip visibility.
Spotify::updatePlaylist($playlist->id)
    ->name('Top 100: final cut')
    ->description('Reordered after another listen')
    ->get();

// Overwrite the tracklist wholesale. Simplest way to persist an edited
// ordering without diffing it. An empty array clears the playlist.
Spotify::replacePlaylistItems($playlist->id, $uris)->get();

// Or move a run of items. insert_before is evaluated against the original
// indices, so moving an item *down* means targeting the slot after it.
Spotify::reorderPlaylistItems($playlist->id)
    ->rangeStart(9)
    ->insertBefore(0)
    ->rangeLength(1)
    ->snapshotId($snapshot)
    ->get();

// Remove every occurrence of these items.
Spotify::removePlaylistItems($playlist->id, ['spotify:track:4iV5W9uYEdYUVa79Axb7Rh'])
    ->snapshotId($snapshot)
    ->get();
```

`createPlaylist()` returns a `Playlist`; the four item mutations return the new
**snapshot id** as a string, and `updatePlaylist()` returns `true` (Spotify
sends an empty body, so failures throw). `reorderPlaylistItems()` and
`removePlaylistItems()` also accept a snapshot id via `->snapshotId()`, which
has Spotify reject a write that raced another edit. Add and replace don't —
Spotify's endpoints for those take no snapshot.

Every one of these endpoints caps a single request at **100 items**. A
larger list throws `ValidationException` before the request is sent, so
chunk it:

```php
foreach (array_chunk($uris, 100) as $chunk) {
    Spotify::addPlaylistItems($playlist->id, $chunk)->get();
}
```

As with reads, `asUser()` targets a specific listener from a job or worker:

```php
Spotify::asUser($userId)->createPlaylist('Top 100')->get();
```

Creation addresses the account by its Spotify user id, which the package
captured when the listener connected. Override it with `->forUser($spotifyUserId)`
when creating on some other account you hold a token for.

### Token refresh & 401 handling

Access tokens are refreshed transparently when stale. A `401` from any API call forces an out-of-band refresh and retries the original request once, so a token revoked between issuance and use is recovered automatically.

If Spotify rejects the refresh token (`invalid_grant`) — typically because the user revoked access from their Spotify settings — the stored row is deleted and `SpotifyDisconnected` is fired with `reason = invalid_grant`, so your app can prompt the user to reconnect.

Concurrent refreshes are serialised per-user via `Cache::lock`, so a fan-out of queue workers doesn't double-spend a rotating refresh token.

### Events

Listen for any of these to integrate with your app:

| Event                        | When                                                            |
|------------------------------|-----------------------------------------------------------------|
| `SpotifyConnected`           | After a successful callback exchange.                           |
| `SpotifyTokenRefreshed`      | After any successful refresh-token grant.                       |
| `SpotifyDisconnected`        | On explicit `disconnect()` or `invalid_grant` from refresh.     |
| `SpotifyConnectFailed`       | State mismatch, user denied consent, authorize error, exchange failure. |

### Disconnecting

```php
Spotify::disconnect();              // current user via guard
Spotify::disconnect($userId);       // explicit
```

Or POST to `route('spotify.disconnect')` from a form. The default route stack includes `web` middleware, so the form must carry a CSRF token:

```blade
<form method="POST" action="{{ route('spotify.disconnect') }}">
    @csrf
    <button type="submit">Disconnect Spotify</button>
</form>
```

### Custom token storage

The default Eloquent-backed repository covers most apps. To swap implementations (Redis, encrypted file, another DB connection), implement `BjTheCod3r\Spotify\Contracts\UserTokenRepository` and point at it:

```php
// config/spotify.php
'oauth' => [
    'token_repository' => App\Spotify\RedisUserTokenRepository::class,
],
```

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
- [x] Albums, Artists, Tracks (Get-by-ID)
- [x] Album tracks (paginated)
- [x] Artist top tracks
- [x] Episodes, Shows, Audiobooks (Get-by-ID)
- [x] Playlists (Get-by-ID, paginated items)
- [x] Users — Authorization Code + PKCE, `me/*` reads
- [x] Playlist mutations (create / edit details / add / replace / reorder / remove)
- [ ] Tracks (audio features / analysis)
- [ ] Browse (categories, new releases, featured playlists)
- [ ] Markets, Genres
- [ ] Player — playback control on the user's active device
- [ ] Playlist cover images

## License

MIT.
