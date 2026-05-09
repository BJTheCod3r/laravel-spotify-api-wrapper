<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Resources;

use Illuminate\Support\Collection;

final class User extends Resource
{
    /**
     * @param array<string, string> $externalUrls
     * @param Collection<int, Image> $images
     */
    public function __construct(
        public readonly string $id,
        public readonly ?string $displayName,
        public readonly string $href,
        public readonly string $uri,
        public readonly string $type,
        public readonly array $externalUrls,
        public readonly Collection $images,
        public readonly ?Followers $followers,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        /** @var array<string, string> $externalUrls */
        $externalUrls = self::arr($data['external_urls'] ?? []);

        $rawFollowers = $data['followers'] ?? null;

        return new self(
            id: (string) ($data['id'] ?? ''),
            displayName: self::str($data['display_name'] ?? null),
            href: (string) ($data['href'] ?? ''),
            uri: (string) ($data['uri'] ?? ''),
            type: (string) ($data['type'] ?? 'user'),
            externalUrls: $externalUrls,
            images: Image::collection($data['images'] ?? []),
            followers: is_array($rawFollowers) ? Followers::fromArray($rawFollowers) : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'display_name' => $this->displayName,
            'href' => $this->href,
            'uri' => $this->uri,
            'type' => $this->type,
            'external_urls' => $this->externalUrls,
            'images' => $this->images->toArray(),
            'followers' => $this->followers?->toArray(),
        ];
    }
}
