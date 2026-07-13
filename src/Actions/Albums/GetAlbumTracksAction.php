<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Albums;

use BjTheCod3r\Spotify\Actions\BaseAction;
use BjTheCod3r\Spotify\Actions\Concerns\HasMarket;
use BjTheCod3r\Spotify\Actions\Concerns\HasPagination;
use BjTheCod3r\Spotify\Resources\Paginated;
use BjTheCod3r\Spotify\Resources\Track;

class GetAlbumTracksAction extends BaseAction
{
    use HasMarket;
    use HasPagination;

    public function id(string $id): static
    {
        $this->pathParameters['id'] = $id;

        return $this;
    }

    protected function path(): string
    {
        return '/albums/{id}/tracks';
    }

    /**
     * @return array<string, mixed>
     */
    protected function query(): array
    {
        return [
            'market' => $this->market,
            'limit' => $this->limit,
            'offset' => $this->offset,
        ];
    }

    /**
     * The endpoint returns simplified tracks (no `album`, `popularity`, or
     * `external_ids`); {@see Track} leaves those properties null.
     *
     * @param array<string, mixed> $payload
     *
     * @return Paginated<Track>
     */
    protected function decode(array $payload): Paginated
    {
        return Paginated::fromArray(
            $payload,
            static fn (array $item): Track => Track::fromArray($item),
        );
    }

    /**
     * @return Paginated<Track>
     */
    public function get(): Paginated
    {
        return $this->execute();
    }
}
