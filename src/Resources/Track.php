<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Resources;

use Illuminate\Support\Collection;

final class Track extends Resource
{
    /**
     * @param array<string, string> $externalUrls
     * @param array<string, string> $externalIds
     * @param Collection<int, Artist> $artists
     * @param Collection<int, string> $availableMarkets
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $href,
        public readonly string $uri,
        public readonly string $type,
        public readonly ?int $durationMs,
        public readonly ?bool $explicit,
        public readonly ?int $popularity,
        public readonly ?string $previewUrl,
        public readonly ?int $trackNumber,
        public readonly ?int $discNumber,
        public readonly ?bool $isLocal,
        public readonly array $externalUrls,
        public readonly array $externalIds,
        public readonly ?Album $album,
        public readonly Collection $artists,
        public readonly Collection $availableMarkets,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        /** @var array<string, string> $externalUrls */
        $externalUrls = self::arr($data['external_urls'] ?? []);

        /** @var array<string, string> $externalIds */
        $externalIds = self::arr($data['external_ids'] ?? []);

        $rawAlbum = $data['album'] ?? null;

        return new self(
            id: (string) ($data['id'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            href: (string) ($data['href'] ?? ''),
            uri: (string) ($data['uri'] ?? ''),
            type: (string) ($data['type'] ?? 'track'),
            durationMs: self::int($data['duration_ms'] ?? null),
            explicit: self::bool($data['explicit'] ?? null),
            popularity: self::int($data['popularity'] ?? null),
            previewUrl: self::str($data['preview_url'] ?? null),
            trackNumber: self::int($data['track_number'] ?? null),
            discNumber: self::int($data['disc_number'] ?? null),
            isLocal: self::bool($data['is_local'] ?? null),
            externalUrls: $externalUrls,
            externalIds: $externalIds,
            album: is_array($rawAlbum) ? Album::fromArray($rawAlbum) : null,
            artists: Artist::collection($data['artists'] ?? []),
            availableMarkets: collect(self::arr($data['available_markets'] ?? []))
                ->filter(static fn (mixed $m): bool => is_string($m))
                ->values(),
        );
    }

    /**
     * @return Collection<int, self>
     */
    public static function collection(mixed $data): Collection
    {
        if (! is_array($data)) {
            return collect();
        }

        return collect($data)
            ->map(static fn (mixed $item): self => self::fromArray(is_array($item) ? $item : []))
            ->values();
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
            'duration_ms' => $this->durationMs,
            'explicit' => $this->explicit,
            'popularity' => $this->popularity,
            'preview_url' => $this->previewUrl,
            'track_number' => $this->trackNumber,
            'disc_number' => $this->discNumber,
            'is_local' => $this->isLocal,
            'external_urls' => $this->externalUrls,
            'external_ids' => $this->externalIds,
            'album' => $this->album?->toArray(),
            'artists' => $this->artists->toArray(),
            'available_markets' => $this->availableMarkets->toArray(),
        ];
    }
}
