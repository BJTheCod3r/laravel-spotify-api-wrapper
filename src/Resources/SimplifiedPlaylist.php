<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Resources;

use Illuminate\Support\Collection;

final class SimplifiedPlaylist extends Resource
{
    /**
     * @param array<string, string> $externalUrls
     * @param Collection<int, Image> $images
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
        public readonly ?string $primaryColor,
        public readonly ?string $snapshotId,
        public readonly array $externalUrls,
        public readonly Collection $images,
        public readonly ?User $owner,
        public readonly ?TracksLink $tracks,
        public readonly ?PlaylistItemsLink $items,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        /** @var array<string, string> $externalUrls */
        $externalUrls = self::arr($data['external_urls'] ?? []);

        $rawOwner = $data['owner'] ?? null;
        $rawTracks = $data['tracks'] ?? null;
        $rawItems = $data['items'] ?? null;

        return new self(
            id: (string) ($data['id'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            href: (string) ($data['href'] ?? ''),
            uri: (string) ($data['uri'] ?? ''),
            type: (string) ($data['type'] ?? 'playlist'),
            description: self::str($data['description'] ?? null),
            public: self::bool($data['public'] ?? null),
            collaborative: self::bool($data['collaborative'] ?? null),
            primaryColor: self::str($data['primary_color'] ?? null),
            snapshotId: self::str($data['snapshot_id'] ?? null),
            externalUrls: $externalUrls,
            images: Image::collection($data['images'] ?? []),
            owner: is_array($rawOwner) ? User::fromArray($rawOwner) : null,
            tracks: is_array($rawTracks) ? TracksLink::fromArray($rawTracks) : null,
            items: is_array($rawItems) ? PlaylistItemsLink::fromArray($rawItems) : null,
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
            'primary_color' => $this->primaryColor,
            'snapshot_id' => $this->snapshotId,
            'external_urls' => $this->externalUrls,
            'images' => $this->images->toArray(),
            'owner' => $this->owner?->toArray(),
            'tracks' => $this->tracks?->toArray(),
            'items' => $this->items?->toArray(),
        ];
    }
}
