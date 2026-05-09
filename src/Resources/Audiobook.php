<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Resources;

final class Audiobook extends Resource
{
    /**
     * @param array<string, string> $externalUrls
     * @param array<int, Image> $images
     * @param array<int, string> $authors
     * @param array<int, string> $narrators
     * @param array<int, string> $languages
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
        public readonly array $images,
        public readonly array $authors,
        public readonly array $narrators,
        public readonly array $languages,
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
            authors: self::namedList($data['authors'] ?? []),
            narrators: self::namedList($data['narrators'] ?? []),
            languages: array_values(array_filter(
                self::arr($data['languages'] ?? []),
                static fn (mixed $l): bool => is_string($l),
            )),
        );
    }

    /**
     * Spotify returns authors/narrators as `[{ "name": "..." }, ...]`.
     * Flatten to a list of names.
     *
     * @return array<int, string>
     */
    private static function namedList(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        $names = [];

        foreach ($data as $entry) {
            if (is_array($entry) && isset($entry['name'])) {
                $names[] = (string) $entry['name'];
            }
        }

        return $names;
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
            'images' => array_map(static fn (Image $i): array => $i->toArray(), $this->images),
            'authors' => $this->authors,
            'narrators' => $this->narrators,
            'languages' => $this->languages,
        ];
    }
}
