<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Resources;

use Illuminate\Support\Collection;

final class TracksLink extends Resource
{
    /**
     * @param Collection<int, PlaylistTrackItem> $items
     */
    public function __construct(
        public readonly string $href,
        public readonly int $total,
        public readonly Collection $items,
        public readonly ?int $limit,
        public readonly ?string $next,
        public readonly ?int $offset,
        public readonly ?string $previous,
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
            items: PlaylistTrackItem::collection($data['items'] ?? []),
            limit: self::int($data['limit'] ?? null),
            next: self::str($data['next'] ?? null),
            offset: self::int($data['offset'] ?? null),
            previous: self::str($data['previous'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $array = [
            'href' => $this->href,
            'total' => $this->total,
        ];

        if ($this->limit !== null || $this->offset !== null || $this->items->isNotEmpty()) {
            $array['items'] = $this->items->toArray();
            $array['limit'] = $this->limit;
            $array['next'] = $this->next;
            $array['offset'] = $this->offset;
            $array['previous'] = $this->previous;
        }

        return $array;
    }
}
