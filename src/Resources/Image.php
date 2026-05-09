<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Resources;

final class Image extends Resource
{
    public function __construct(
        public readonly string $url,
        public readonly ?int $height,
        public readonly ?int $width,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            url: (string) ($data['url'] ?? ''),
            height: self::int($data['height'] ?? null),
            width: self::int($data['width'] ?? null),
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
            'url' => $this->url,
            'height' => $this->height,
            'width' => $this->width,
        ];
    }
}
