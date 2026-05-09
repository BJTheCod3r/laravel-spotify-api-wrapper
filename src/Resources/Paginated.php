<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Resources;

/**
 * Spotify's paging object. Holds the result page plus cursor metadata.
 *
 * `$items` is a typed `array<int, T>` where T is the element resource;
 * the type is enforced via the factory callable passed to `fromArray()`.
 *
 * @template T
 */
final class Paginated extends Resource
{
    /**
     * @param array<int, T> $items
     */
    public function __construct(
        public readonly string $href,
        public readonly array $items,
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
        /** @var array<int, array<string, mixed>> $rawItems */
        $rawItems = self::arr($data['items'] ?? []);

        return new self(
            href: (string) ($data['href'] ?? ''),
            items: array_values(array_map(
                static fn (mixed $item) => $itemFactory(is_array($item) ? $item : []),
                $rawItems,
            )),
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
            'items' => array_map(
                static fn (mixed $item): mixed => $item instanceof Resource ? $item->toArray() : $item,
                $this->items,
            ),
            'limit' => $this->limit,
            'next' => $this->next,
            'offset' => $this->offset,
            'previous' => $this->previous,
            'total' => $this->total,
        ];
    }
}
