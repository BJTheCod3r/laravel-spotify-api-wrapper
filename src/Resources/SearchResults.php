<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Resources;

use BjTheCod3r\Spotify\Enums\SearchType;

/**
 * Typed root for the multi-type search response. Each property is null
 * unless that type was included in the request's `type` parameter.
 *
 * @property-read Paginated<Track>|null     $tracks
 * @property-read Paginated<Album>|null     $albums
 * @property-read Paginated<Artist>|null    $artists
 * @property-read Paginated<Playlist>|null  $playlists
 * @property-read Paginated<Show>|null      $shows
 * @property-read Paginated<Episode>|null   $episodes
 * @property-read Paginated<Audiobook>|null $audiobooks
 */
final class SearchResults extends Resource
{
    public function __construct(
        public readonly ?Paginated $tracks = null,
        public readonly ?Paginated $albums = null,
        public readonly ?Paginated $artists = null,
        public readonly ?Paginated $playlists = null,
        public readonly ?Paginated $shows = null,
        public readonly ?Paginated $episodes = null,
        public readonly ?Paginated $audiobooks = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $pages = [];

        foreach (SearchType::cases() as $type) {
            $key = $type->pluralKey();
            $raw = $data[$key] ?? null;

            $pages[$key] = is_array($raw)
                ? Paginated::fromArray($raw, $type->resourceFactory())
                : null;
        }

        return new self(...$pages);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $output = [];

        foreach (SearchType::cases() as $type) {
            $key = $type->pluralKey();
            $page = $this->{$key};

            if ($page !== null) {
                $output[$key] = $page->toArray();
            }
        }

        return $output;
    }
}
