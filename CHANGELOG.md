# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Add GitHub Actions test workflow for PHP 8.2, 8.3, and 8.4, including lowest dependency coverage on PHP 8.2.
- Add `Spotify::playlist($id)` for retrieving a full playlist from Spotify.
- Add playlist resources for full playlist responses, simplified playlist search results, playlist item links, and playlist track items.

### Changed

- Hydrate search playlist results as `SimplifiedPlaylist` and direct playlist lookups as `Playlist`.
- Filter null entries from paginated API item collections before resource hydration.
