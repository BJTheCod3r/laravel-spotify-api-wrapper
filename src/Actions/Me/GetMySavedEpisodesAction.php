<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Me;

use BjTheCod3r\Spotify\Actions\BaseAction;
use BjTheCod3r\Spotify\Actions\Concerns\HasMarket;
use BjTheCod3r\Spotify\Actions\Concerns\HasPagination;
use BjTheCod3r\Spotify\Resources\Episode;
use BjTheCod3r\Spotify\Resources\Paginated;

class GetMySavedEpisodesAction extends BaseAction
{
    use HasMarket;
    use HasPagination;

    protected function path(): string
    {
        return '/me/episodes';
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
     * @return Paginated<Episode>
     */
    protected function decode(array $payload): Paginated
    {
        return Paginated::fromArray(
            $payload,
            static function (array $item): Episode {
                $episode = $item['episode'] ?? [];

                return Episode::fromArray(is_array($episode) ? $episode : []);
            },
        );
    }

    /**
     * @return Paginated<Episode>
     */
    public function get(): Paginated
    {
        return $this->execute();
    }
}
