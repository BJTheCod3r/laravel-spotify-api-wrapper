<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Me;

use BjTheCod3r\Spotify\Actions\BaseAction;
use BjTheCod3r\Spotify\Actions\Concerns\HasPagination;
use BjTheCod3r\Spotify\Resources\Paginated;
use BjTheCod3r\Spotify\Resources\Track;

class GetMyTopTracksAction extends BaseAction
{
    use HasPagination;

    protected ?string $timeRange = null;

    /**
     * Window for the top items query — one of "short_term" (~4 weeks),
     * "medium_term" (~6 months, Spotify's default), or "long_term" (years).
     */
    public function timeRange(?string $range): static
    {
        $this->timeRange = $range;

        return $this;
    }

    protected function path(): string
    {
        return '/me/top/tracks';
    }

    /**
     * @return array<string, mixed>
     */
    protected function query(): array
    {
        return [
            'time_range' => $this->timeRange,
            'limit' => $this->limit,
            'offset' => $this->offset,
        ];
    }

    /**
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
