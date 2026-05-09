<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Resources;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class Episode extends Resource
{
    /**
     * @param array<string, string> $externalUrls
     * @param Collection<int, Image> $images
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
        public readonly ?int $durationMs,
        public readonly ?bool $explicit,
        public readonly ?Carbon $releaseDate,
        public readonly ?string $releaseDatePrecision,
        public readonly ?string $audioPreviewUrl,
        public readonly ?bool $isExternallyHosted,
        public readonly ?bool $isPlayable,
        public readonly array $externalUrls,
        public readonly Collection $images,
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
            type: (string) ($data['type'] ?? 'episode'),
            description: self::str($data['description'] ?? null),
            htmlDescription: self::str($data['html_description'] ?? null),
            durationMs: self::int($data['duration_ms'] ?? null),
            explicit: self::bool($data['explicit'] ?? null),
            releaseDate: self::date($data['release_date'] ?? null),
            releaseDatePrecision: self::str($data['release_date_precision'] ?? null),
            audioPreviewUrl: self::str($data['audio_preview_url'] ?? null),
            isExternallyHosted: self::bool($data['is_externally_hosted'] ?? null),
            isPlayable: self::bool($data['is_playable'] ?? null),
            externalUrls: $externalUrls,
            images: Image::collection($data['images'] ?? []),
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
            'duration_ms' => $this->durationMs,
            'explicit' => $this->explicit,
            'release_date' => self::formatDate($this->releaseDate, $this->releaseDatePrecision),
            'release_date_precision' => $this->releaseDatePrecision,
            'audio_preview_url' => $this->audioPreviewUrl,
            'is_externally_hosted' => $this->isExternallyHosted,
            'is_playable' => $this->isPlayable,
            'external_urls' => $this->externalUrls,
            'images' => $this->images->toArray(),
            'languages' => $this->languages->toArray(),
        ];
    }
}
