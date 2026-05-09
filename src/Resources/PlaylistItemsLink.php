<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Resources;

final class PlaylistItemsLink extends Resource
{
    public function __construct(
        public readonly string $href,
        public readonly int $total,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            href: (string) ($data['href'] ?? ''),
            total: (int) ($data['total'] ?? 0),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'href' => $this->href,
            'total' => $this->total,
        ];
    }
}
