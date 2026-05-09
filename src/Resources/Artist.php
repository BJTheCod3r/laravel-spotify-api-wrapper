<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Resources;

final class Artist extends Resource
{
    /**
     * @param array<string, string> $externalUrls
     * @param array<int, string> $genres
     * @param array<int, Image> $images
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $href,
        public readonly string $uri,
        public readonly string $type,
        public readonly array $externalUrls,
        public readonly array $genres,
        public readonly array $images,
        public readonly ?int $popularity,
        public readonly ?int $followersTotal,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        /** @var array<string, string> $externalUrls */
        $externalUrls = self::arr($data['external_urls'] ?? []);

        $followers = self::arr($data['followers'] ?? []);

        /** @var array<int, string> $genres */
        $genres = array_values(array_filter(
            self::arr($data['genres'] ?? []),
            static fn (mixed $g): bool => is_string($g),
        ));

        return new self(
            id: (string) ($data['id'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            href: (string) ($data['href'] ?? ''),
            uri: (string) ($data['uri'] ?? ''),
            type: (string) ($data['type'] ?? 'artist'),
            externalUrls: $externalUrls,
            genres: $genres,
            images: Image::collection($data['images'] ?? []),
            popularity: self::int($data['popularity'] ?? null),
            followersTotal: self::int($followers['total'] ?? null),
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
            'external_urls' => $this->externalUrls,
            'genres' => $this->genres,
            'images' => array_map(static fn (Image $i): array => $i->toArray(), $this->images),
            'popularity' => $this->popularity,
            'followers_total' => $this->followersTotal,
        ];
    }
}
