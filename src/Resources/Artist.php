<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Resources;

use Illuminate\Support\Collection;

final class Artist extends Resource
{
    /**
     * @param array<string, string> $externalUrls
     * @param Collection<int, string> $genres
     * @param Collection<int, Image> $images
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $href,
        public readonly string $uri,
        public readonly string $type,
        public readonly array $externalUrls,
        public readonly Collection $genres,
        public readonly Collection $images,
        public readonly ?int $popularity,
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
            name: (string) ($data['name'] ?? ''),
            href: (string) ($data['href'] ?? ''),
            uri: (string) ($data['uri'] ?? ''),
            type: (string) ($data['type'] ?? 'artist'),
            externalUrls: $externalUrls,
            genres: collect(self::arr($data['genres'] ?? []))
                ->filter(static fn (mixed $g): bool => is_string($g))
                ->values(),
            images: Image::collection($data['images'] ?? []),
            popularity: self::int($data['popularity'] ?? null),
            followers: is_array($rawFollowers) ? Followers::fromArray($rawFollowers) : null,
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
            'external_urls' => $this->externalUrls,
            'genres' => $this->genres->toArray(),
            'images' => $this->images->toArray(),
            'popularity' => $this->popularity,
            'followers' => $this->followers?->toArray(),
        ];
    }
}
