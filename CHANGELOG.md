# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

> **Maintenance rule:** the `[Unreleased]` section must be updated whenever
> changes are pushed. Before every push, read the diff of the current branch
> against its base (`git diff <base>...HEAD`) and reflect the full set of
> branch-level changes here — not only the latest commit. If an entry for the
> same change already exists from an earlier commit on this branch, edit it in
> place instead of appending a duplicate. The goal is one coherent set of
> bullets per PR, regardless of how many commits the branch contains. Released
> sections are dated `YYYY-MM-DD`, e.g. `## [1.0.0] - 2026-05-09`.

## [Unreleased]

## [0.7.0] - 2026-07-30

### Added

- Add playlist mutations, all user-context and requiring the `playlist-modify-public` / `playlist-modify-private` scopes. These are not in `oauth.default_scopes`, so request them at connect time.
  - `Spotify::createPlaylist($name)` returns the new `Playlist`. Owner defaults to the connected account; override with `->forUser($spotifyUserId)`. Also takes `->description()`, `->public()`, `->collaborative()`.
  - `Spotify::updatePlaylist($id)` renames, re-describes, or flips visibility. Returns `true`; Spotify sends an empty body, so failures surface as exceptions.
  - `Spotify::addPlaylistItems($id, $uris)` appends, or inserts at `->position()`.
  - `Spotify::replacePlaylistItems($id, $uris)` overwrites the tracklist; an empty list clears it.
  - `Spotify::reorderPlaylistItems($id)` moves a run via `->rangeStart()`, `->insertBefore()`, `->rangeLength()`.
  - `Spotify::removePlaylistItems($id, $uris)` removes every occurrence of each item.
  - The four item mutations return the resulting snapshot id as a string. `reorderPlaylistItems()` and `removePlaylistItems()` also take `->snapshotId()` for optimistic concurrency; Spotify's add and replace endpoints accept no snapshot.
- Item lists accept bare track ids and `open.spotify.com` links (including `/embed/` and locale-prefixed `/intl-xx/` forms) alongside `spotify:` URIs. A bare id is the one ambiguous case and is assumed to be a track, so episodes must be passed as full URIs. Input in none of those forms throws `ValidationException` rather than reaching Spotify as a malformed URI, as do lists longer than Spotify's 100-item-per-request cap.
- Add `Spotify::playlistItems($id)` for paging through a playlist's tracklist (`Paginated<PlaylistTrackItem>`), with `->market()`, `->fields()`, `->limit()`, `->offset()`, and `->additionalTypes()`. `Spotify::playlist($id)` only carries the first page inline.
- Add `OAuthManager::spotifyUserId()`, which resolves the Spotify account id linked to a local user. The capture is best-effort at connect time, so rows stored before the id was captured are backfilled from `/me` on first use.

### Changed

- Playlist mutations resolve their user client the same way `me()` does: the user bound via `asUser()`, otherwise the current user from the configured guard.

## [0.6.0] - 2026-07-30

### Added

- Hydrate `available_markets` into `Album::$availableMarkets`, `Track::$availableMarkets`, and `Audiobook::$availableMarkets` (`Collection<string>`), and include it in each `toArray()`. Spotify sends the field on these objects but it was being discarded; `Show` already had it. `Episode` is deliberately excluded — Spotify only returns `available_markets` on the episode's nested show, not on the episode itself. Note that Spotify replaces the field with `is_playable` when a `market` is sent, so market-scoped calls (including the search endpoints and `artistTopTracks()`, which seed `spotify.defaults.market`) hydrate an empty collection.

### Changed

- **Breaking:** `Album`, `Track`, and `Audiobook` constructors take a new trailing `Collection $availableMarkets` argument. Callers hydrating these resources via `new` rather than `fromArray()` must pass it.

## [0.5.0] - 2026-07-21

### Added

- Add `Spotify::artistTopTracks($id)` (`GetArtistTopTracksAction`) for an artist's most popular tracks. Accepts `->market()` and returns a `Collection<Track>` — the endpoint sends a bare list rather than a paging object. Unlike the other Get-by-ID actions it seeds `spotify.defaults.market`, because Spotify returns nothing at all when it has neither a market nor a user token to infer one from.
- Add `Track::collection()`, mirroring the other resources' list hydrators.

## [0.4.0] - 2026-07-13

### Added

- Add `Spotify::albumTracks($id)` (`GetAlbumTracksAction`) for paging through an album's tracks. Returns a `Paginated<Track>` and accepts `->market()`, `->limit()`, and `->offset()`. Items are Spotify's simplified tracks, so `album`, `popularity`, and `externalIds` are null — use `Spotify::track($id)` for the full object.

## [0.3.0] - 2026-05-23

### Added

- Add user OAuth via Authorization Code + PKCE: `Spotify::redirect()`, `Spotify::handleCallback()`, `Spotify::disconnect()`, opt-in package routes (`spotify.connect`, `spotify.callback`, `spotify.disconnect`), and a publishable `spotify_user_tokens` migration with access/refresh tokens encrypted at rest.
- Add `Spotify::me()` and `Spotify::asUser($id)` for user-context calls, plus the `Me\Get*Action` family: `GetCurrentUserProfileAction`, `GetMyPlaylistsAction`, `GetMySavedTracksAction`, `GetMySavedAlbumsAction`, `GetMySavedShowsAction`, `GetMySavedEpisodesAction`, `GetMySavedAudiobooksAction`, `GetMyTopTracksAction`, `GetMyTopArtistsAction`, `GetMyRecentlyPlayedAction`, `GetFollowedArtistsAction`.
- Add `UserTokenRepository` contract with a default Eloquent implementation (`EloquentUserTokenRepository` over the `SpotifyUserToken` model). Swap via `oauth.token_repository`.
- Add `UserTokenProvider` that transparently refreshes access tokens, serialises concurrent refreshes per-user via `Cache::lock`, recovers from a `401` by force-refreshing and retrying once, and deletes the row + fires `SpotifyDisconnected` on `invalid_grant`.
- Add events `SpotifyConnected`, `SpotifyTokenRefreshed`, `SpotifyDisconnected`, `SpotifyConnectFailed` so apps can react to lifecycle changes. `SpotifyConnectFailed::REASON_AUTHORIZE_ERROR` distinguishes non-`access_denied` callback errors (`invalid_scope`, `server_error`, …) from a true user denial.
- Add `oauth` configuration block (redirect URI, default scopes, guard, routes, session key, after-connect/disconnect redirects, refresh lock, custom token repository).
- Flash `spotify.oauth.error` (`{reason, description}`) onto the session on any callback failure so the after-connect destination can render error UX without subscribing to events.

### Changed

- `SpotifyConfig` now exposes an `oauth: OAuthConfig` sub-DTO derived from `config/spotify.php`.
- README adds a "User authentication" section covering setup, the connect flow, reading user data, refresh semantics, events, custom token storage, and a CSRF-aware disconnect form example.
- Skip OAuth route registration when `routesAreCached()` returns true, so `php artisan route:cache` is the single source of truth in production.
- `AuthorizationCodeExchanger` and the refresh-token call in `UserTokenProvider` now send an explicit `Accept: application/json` for parity with the rest of the package.

### Fixed

- `UserTokenSet::fromTokenResponse()` now throws `AuthenticationException::malformedTokenResponse()` when Spotify returns a 2xx without both `access_token` and `refresh_token`, instead of silently persisting an empty row and firing `SpotifyConnected`.
- A failed code exchange during callback now routes through the same `SpotifyConnectFailed` + session-flash pipeline as the other failure modes, rather than bubbling a bare `AuthenticationException` past the controller.

## [0.2.0] - 2026-05-20

### Added

- Add `Spotify::album($id)`, `Spotify::artist($id)`, `Spotify::track($id)`, `Spotify::show($id)`, `Spotify::episode($id)`, `Spotify::audiobook($id)`, and `Spotify::user($id)` for retrieving individual resources by ID; market-scoped endpoints expose `->market()`.

## [0.1.0] - 2026-05-09

### Added

- Add GitHub Actions test workflow for PHP 8.2, 8.3, and 8.4, including lowest dependency coverage on PHP 8.2.
- Add `Spotify::playlist($id)` for retrieving a full playlist from Spotify.
- Add playlist resources for full playlist responses, simplified playlist search results, playlist item links, and playlist track items.

### Changed

- Hydrate search playlist results as `SimplifiedPlaylist` and direct playlist lookups as `Playlist`.
- Filter null entries from paginated API item collections before resource hydration.
