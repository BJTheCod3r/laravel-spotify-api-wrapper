<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Resources;

use Illuminate\Support\Collection;

/**
 * @template T
 */
final class Paginated extends Resource
{
    /**
     * @param Collection<int, T> $items
     */
    public function __construct(
        public readonly string $href,
        public readonly Collection $items,
        public readonly int $limit,
        public readonly ?string $next,
        public readonly int $offset,
        public readonly ?string $previous,
        public readonly int $total,
    ) {
    }

    /**
     * @template U
     *
     * @param array<string, mixed> $data
     * @param callable(array<string, mixed>): U $itemFactory
     *
     * @return self<U>
     */
    public static function fromArray(array $data, callable $itemFactory): self
    {
        $rawItems = self::arr($data['items'] ?? []);

        return new self(
            href: (string) ($data['href'] ?? ''),
            items: collect($rawItems)
                ->filter(static fn (mixed $item): bool => is_array($item))
                ->map(static fn (array $item) => $itemFactory($item))
                ->values(),
            limit: (int) ($data['limit'] ?? 0),
            next: self::str($data['next'] ?? null),
            offset: (int) ($data['offset'] ?? 0),
            previous: self::str($data['previous'] ?? null),
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
            'items' => $this->items->toArray(),
            'limit' => $this->limit,
            'next' => $this->next,
            'offset' => $this->offset,
            'previous' => $this->previous,
            'total' => $this->total,
        ];
    }
}
