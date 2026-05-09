<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Resources;

use Illuminate\Support\Collection;

final class Audiobook extends Resource
{
    /**
     * @param array<string, string> $externalUrls
     * @param Collection<int, Image> $images
     * @param Collection<int, Author> $authors
     * @param Collection<int, Narrator> $narrators
     * @param Collection<int, string> $languages
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $href,
        public readonly string $uri,
        public readonly string $type,
        public readonly ?string $description,
        public readonly ?string $htmlDescription,
        public readonly ?string $edition,
        public readonly ?bool $explicit,
        public readonly ?string $publisher,
        public readonly ?int $totalChapters,
        public readonly array $externalUrls,
        public readonly Collection $images,
        public readonly Collection $authors,
        public readonly Collection $narrators,
        public readonly Collection $languages,
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
            type: (string) ($data['type'] ?? 'audiobook'),
            description: self::str($data['description'] ?? null),
            htmlDescription: self::str($data['html_description'] ?? null),
            edition: self::str($data['edition'] ?? null),
            explicit: self::bool($data['explicit'] ?? null),
            publisher: self::str($data['publisher'] ?? null),
            totalChapters: self::int($data['total_chapters'] ?? null),
            externalUrls: $externalUrls,
            images: Image::collection($data['images'] ?? []),
            authors: Author::collection($data['authors'] ?? []),
            narrators: Narrator::collection($data['narrators'] ?? []),
            languages: collect(self::arr($data['languages'] ?? []))
                ->filter(static fn (mixed $l): bool => is_string($l))
                ->values(),
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
            'edition' => $this->edition,
            'explicit' => $this->explicit,
            'publisher' => $this->publisher,
            'total_chapters' => $this->totalChapters,
            'external_urls' => $this->externalUrls,
            'images' => $this->images->toArray(),
            'authors' => $this->authors->toArray(),
            'narrators' => $this->narrators->toArray(),
            'languages' => $this->languages->toArray(),
        ];
    }
}
