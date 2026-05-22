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
> bullets per PR, regardless of how many commits the branch contains.

## [Unreleased]

Use `YYYY-MM-DD` dates for released sections, for example:

```markdown
## [1.0.0] - 2026-05-09
```

### Added

- Add user OAuth via Authorization Code + PKCE: `Spotify::redirect()`, `Spotify::handleCallback()`, `Spotify::disconnect()`, opt-in package routes (`spotify.connect`, `spotify.callback`, `spotify.disconnect`), and a publishable `spotify_user_tokens` migration with access/refresh tokens encrypted at rest.
- Add `Spotify::me()` and `Spotify::asUser($id)` for user-context calls, plus the `Me\Get*Action` family: `GetCurrentUserProfileAction`, `GetMyPlaylistsAction`, `GetMySavedTracksAction`, `GetMySavedAlbumsAction`, `GetMySavedShowsAction`, `GetMySavedEpisodesAction`, `GetMySavedAudiobooksAction`, `GetMyTopTracksAction`, `GetMyTopArtistsAction`, `GetMyRecentlyPlayedAction`, `GetFollowedArtistsAction`.
- Add `UserTokenRepository` contract with a default Eloquent implementation (`EloquentUserTokenRepository` over the `SpotifyUserToken` model). Swap via `oauth.token_repository`.
- Add `UserTokenProvider` that transparently refreshes access tokens, serialises concurrent refreshes per-user via `Cache::lock`, recovers from a `401` by force-refreshing and retrying once, and deletes the row + fires `SpotifyDisconnected` on `invalid_grant`.
- Add events `SpotifyConnected`, `SpotifyTokenRefreshed`, `SpotifyDisconnected`, `SpotifyConnectFailed` so apps can react to lifecycle changes.
- Add `oauth` configuration block (redirect URI, default scopes, guard, routes, session key, after-connect/disconnect redirects, refresh lock, custom token repository).

### Changed

- `SpotifyConfig` now exposes an `oauth: OAuthConfig` sub-DTO derived from `config/spotify.php`.
- README adds a "User authentication" section covering setup, the connect flow, reading user data, refresh semantics, events, and custom token storage. The roadmap is updated to reflect what shipped.

### Fixed

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
