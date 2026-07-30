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

        $trimmed = array_filter(
            array_map(static fn (string $uri): string => trim($uri), $list),
            static fn (string $uri): bool => $uri !== '',
        );

        $this->uris = array_values(array_map(
            static fn (string $uri): string => self::normalizeUri($uri),
            $trimmed,
        ));

        return $this;
    }

    protected static function normalizeUri(string $uri): string
    {
        if (str_starts_with($uri, 'spotify:')) {
            return $uri;
        }

        if (preg_match('#open\.spotify\.com/(?:[^/]+/)*(track|episode)/([a-zA-Z0-9]+)#', $uri, $matches) === 1) {
            return "spotify:{$matches[1]}:{$matches[2]}";
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
    }
}
