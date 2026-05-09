<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Resources;

final class Show extends Resource
{
    /**
     * @param array<string, string> $externalUrls
     * @param array<int, Image> $images
     * @param array<int, string> $languages
     * @param array<int, string> $availableMarkets
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $href,
        public readonly string $uri,
        public readonly string $type,
        public readonly ?string $description,
        public readonly ?string $htmlDescription,
        public readonly ?bool $explicit,
        public readonly ?bool $isExternallyHosted,
        public readonly ?string $mediaType,
        public readonly ?string $publisher,
        public readonly ?int $totalEpisodes,
        public readonly array $externalUrls,
        public readonly array $images,
        public readonly array $languages,
        public readonly array $availableMarkets,
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
            type: (string) ($data['type'] ?? 'show'),
            description: self::str($data['description'] ?? null),
            htmlDescription: self::str($data['html_description'] ?? null),
            explicit: self::bool($data['explicit'] ?? null),
            isExternallyHosted: self::bool($data['is_externally_hosted'] ?? null),
            mediaType: self::str($data['media_type'] ?? null),
            publisher: self::str($data['publisher'] ?? null),
            totalEpisodes: self::int($data['total_episodes'] ?? null),
            externalUrls: $externalUrls,
            images: Image::collection($data['images'] ?? []),
            languages: array_values(array_filter(
                self::arr($data['languages'] ?? []),
                static fn (mixed $l): bool => is_string($l),
            )),
            availableMarkets: array_values(array_filter(
                self::arr($data['available_markets'] ?? []),
                static fn (mixed $m): bool => is_string($m),
            )),
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
            'html_description' => $this->htmlDescription,
            'explicit' => $this->explicit,
            'is_externally_hosted' => $this->isExternallyHosted,
            'media_type' => $this->mediaType,
            'publisher' => $this->publisher,
            'total_episodes' => $this->totalEpisodes,
            'external_urls' => $this->externalUrls,
            'images' => array_map(static fn (Image $i): array => $i->toArray(), $this->images),
            'languages' => $this->languages,
            'available_markets' => $this->availableMarkets,
        ];
    }
}
