<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Concerns;

use BjTheCod3r\Spotify\Exceptions\ValidationException;

/**
 * Item lists for the playlist-mutation endpoints.
 *
 * Spotify addresses items by URI (`spotify:track:ID`), so bare ids and
 * open.spotify.com links are normalised to that form. A bare id is the one
 * ambiguous case and is assumed to be a track, so pass episodes as full URIs.
 * Anything that is none of those three forms throws rather than reaching
 * Spotify as a malformed URI.
 *
 * Every one of these endpoints caps a single request at 100 items.
 */
trait HasPlaylistItemUris
{
    protected const MAX_ITEMS = 100;

    /** @var list<string> */
    protected array $uris = [];

    /**
     * @param list<string>|string $uris Track URIs, ids, or open.spotify.com links.
     */
    public function uris(array|string $uris): static
    {
        $list = is_string($uris) ? [$uris] : $uris;

        $this->uris = array_values(array_filter(
            array_map(static fn (string $uri): string => trim($uri), $list),
            static fn (string $uri): bool => $uri !== '',
        ));

        return $this;
    }

    protected static function normalizeUri(string $uri): string
    {
        if (str_starts_with($uri, 'spotify:')) {
            return $uri;
        }

        if (preg_match('#open\.spotify\.com/(?:[^/]+/)*(track|episode)/([a-zA-Z0-9]+)#i', $uri, $matches) === 1) {
            return 'spotify:'.strtolower($matches[1]).":{$matches[2]}";
        }

        if (preg_match('/^[a-zA-Z0-9]{22}$/', $uri) !== 1) {
            throw new ValidationException(
                "[{$uri}] is not a track id, a spotify: URI, or an open.spotify.com track/episode link.",
            );
        }

        return 'spotify:track:'.$uri;
    }

    /**
     * @param bool $allowEmpty Replace accepts an empty list, which clears the playlist.
     */
    protected function guardUris(bool $allowEmpty = false): void
    {
        if (! $allowEmpty && $this->uris === []) {
            throw new ValidationException('At least one track URI is required.');
        }

        if (count($this->uris) > self::MAX_ITEMS) {
            throw new ValidationException(sprintf(
                'Spotify accepts at most %d items per playlist request, %d given. Send them in batches.',
                self::MAX_ITEMS,
                count($this->uris),
            ));
        }

        $this->uris = array_map(self::normalizeUri(...), $this->uris);
    }
}
