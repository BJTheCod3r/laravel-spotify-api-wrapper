<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Resources;

final class Playlist extends Resource
{
    /**
     * @param array<string, string> $externalUrls
     * @param array<int, Image> $images
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $href,
        public readonly string $uri,
        public readonly string $type,
        public readonly ?string $description,
        public readonly ?bool $public,
        public readonly ?bool $collaborative,
        public readonly ?string $snapshotId,
        public readonly ?string $ownerId,
        public readonly ?string $ownerDisplayName,
        public readonly ?int $tracksTotal,
        public readonly array $externalUrls,
        public readonly array $images,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        /** @var array<string, string> $externalUrls */
        $externalUrls = self::arr($data['external_urls'] ?? []);

        $owner = self::arr($data['owner'] ?? []);
        $tracks = self::arr($data['tracks'] ?? []);

        return new self(
            id: (string) ($data['id'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            href: (string) ($data['href'] ?? ''),
            uri: (string) ($data['uri'] ?? ''),
            type: (string) ($data['type'] ?? 'playlist'),
            description: self::str($data['description'] ?? null),
            public: self::bool($data['public'] ?? null),
            collaborative: self::bool($data['collaborative'] ?? null),
            snapshotId: self::str($data['snapshot_id'] ?? null),
            ownerId: self::str($owner['id'] ?? null),
            ownerDisplayName: self::str($owner['display_name'] ?? null),
            tracksTotal: self::int($tracks['total'] ?? null),
            externalUrls: $externalUrls,
            images: Image::collection($data['images'] ?? []),
        );
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
            'description' => $this->description,
            'public' => $this->public,
            'collaborative' => $this->collaborative,
            'snapshot_id' => $this->snapshotId,
            'owner_id' => $this->ownerId,
            'owner_display_name' => $this->ownerDisplayName,
            'tracks_total' => $this->tracksTotal,
            'external_urls' => $this->externalUrls,
            'images' => array_map(static fn (Image $i): array => $i->toArray(), $this->images),
        ];
    }
}
