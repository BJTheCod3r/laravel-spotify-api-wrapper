<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Playlists;

use BjTheCod3r\Spotify\Actions\BaseAction;
use BjTheCod3r\Spotify\Actions\Concerns\HasMarket;
use BjTheCod3r\Spotify\Actions\Concerns\HasPagination;
use BjTheCod3r\Spotify\Resources\Paginated;
use BjTheCod3r\Spotify\Resources\PlaylistTrackItem;

/**
 * A playlist's items, paginated. `Spotify::playlist($id)` carries the first
 * page inline; this action is how you walk past it, and how you read back
 * the current ordering before editing it.
 */
class GetPlaylistItemsAction extends BaseAction
{
    use HasMarket;
    use HasPagination;

    protected ?string $fields = null;

    protected ?string $additionalTypes = null;

    public function id(string $id): static
    {
        $this->pathParameters['id'] = $id;

        return $this;
    }

    public function fields(?string $fields): static
    {
        $this->fields = $fields;

        return $this;
    }

    public function additionalTypes(string|array|null $types): static
    {
        if (is_array($types)) {
            $types = implode(',', $types);
        }

        $this->additionalTypes = $types;

        return $this;
    }

    protected function path(): string
    {
        return '/playlists/{id}/tracks';
    }

    /**
     * @return array<string, mixed>
     */
    protected function query(): array
    {
        return [
            'market' => $this->market,
            'fields' => $this->fields,
            'limit' => $this->limit,
            'offset' => $this->offset,
            'additional_types' => $this->additionalTypes,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return Paginated<PlaylistTrackItem>
     */
    protected function decode(array $payload): Paginated
    {
        return Paginated::fromArray(
            $payload,
            static fn (array $item): PlaylistTrackItem => PlaylistTrackItem::fromArray($item),
        );
    }

    /**
     * @return Paginated<PlaylistTrackItem>
     */
    public function get(): Paginated
    {
        return $this->execute();
    }
}
