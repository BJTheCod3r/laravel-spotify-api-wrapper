<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Me;

use BjTheCod3r\Spotify\Actions\BaseAction;
use BjTheCod3r\Spotify\Actions\Concerns\HasMarket;
use BjTheCod3r\Spotify\Actions\Concerns\HasPagination;
use BjTheCod3r\Spotify\Resources\Paginated;
use BjTheCod3r\Spotify\Resources\Track;

class GetMySavedTracksAction extends BaseAction
{
    use HasMarket;
    use HasPagination;

    protected function path(): string
    {
        return '/me/tracks';
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
     * Spotify wraps each item as `{ added_at, track: {...} }`. The wrapper's
     * timestamp is dropped here — only the inner Track is hydrated.
     *
     * @param array<string, mixed> $payload
     *
     * @return Paginated<Track>
     */
    protected function decode(array $payload): Paginated
    {
        return Paginated::fromArray(
            $payload,
            static function (array $item): Track {
                $track = $item['track'] ?? [];

                return Track::fromArray(is_array($track) ? $track : []);
            },
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
