<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Me;

use BjTheCod3r\Spotify\Actions\BaseAction;
use BjTheCod3r\Spotify\Actions\Concerns\HasMarket;
use BjTheCod3r\Spotify\Actions\Concerns\HasPagination;
use BjTheCod3r\Spotify\Resources\Album;
use BjTheCod3r\Spotify\Resources\Paginated;

class GetMySavedAlbumsAction extends BaseAction
{
    use HasMarket;
    use HasPagination;

    protected function path(): string
    {
        return '/me/albums';
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
     * @param array<string, mixed> $payload
     *
     * @return Paginated<Album>
     */
    protected function decode(array $payload): Paginated
    {
        return Paginated::fromArray(
            $payload,
            static function (array $item): Album {
                $album = $item['album'] ?? [];

                return Album::fromArray(is_array($album) ? $album : []);
            },
        );
    }

    /**
     * @return Paginated<Album>
     */
    public function get(): Paginated
    {
        return $this->execute();
    }
}
