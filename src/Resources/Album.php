<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Resources;

use Illuminate\Support\Carbon;

final class Album extends Resource
{
    /**
     * @param array<string, string> $externalUrls
     * @param array<int, Image> $images
     * @param array<int, Artist> $artists
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $href,
        public readonly string $uri,
        public readonly string $type,
        public readonly ?string $albumType,
        public readonly ?int $totalTracks,
        public readonly ?Carbon $releaseDate,
        public readonly ?string $releaseDatePrecision,
        public readonly array $externalUrls,
        public readonly array $images,
        public readonly array $artists,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        /** @var array<string, string> $externalUrls */
        $externalUrls = self::arr($data['external_urls'] ?? []);

        return new self(
            id: (string) ($data['id'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            href: (string) ($data['href'] ?? ''),
            uri: (string) ($data['uri'] ?? ''),
            type: (string) ($data['type'] ?? 'album'),
            albumType: self::str($data['album_type'] ?? null),
            totalTracks: self::int($data['total_tracks'] ?? null),
            releaseDate: self::date($data['release_date'] ?? null),
            releaseDatePrecision: self::str($data['release_date_precision'] ?? null),
            externalUrls: $externalUrls,
            images: Image::collection($data['images'] ?? []),
            artists: Artist::collection($data['artists'] ?? []),
        );
    }

    /**
     * @param array<int, array<string, mixed>>|mixed $data
     *
     * @return array<int, self>
     */
    public static function collection(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        return array_values(array_map(
            static fn (mixed $item): self => self::fromArray(is_array($item) ? $item : []),
            $data,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'href' => $this->href,
            'uri' => $this->uri,
            'type' => $this->type,
            'album_type' => $this->albumType,
            'total_tracks' => $this->totalTracks,
            'release_date' => self::formatDate($this->releaseDate, $this->releaseDatePrecision),
            'release_date_precision' => $this->releaseDatePrecision,
            'external_urls' => $this->externalUrls,
            'images' => array_map(static fn (Image $i): array => $i->toArray(), $this->images),
            'artists' => array_map(static fn (Artist $a): array => $a->toArray(), $this->artists),
        ];
    }
}
