<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Me;

use BjTheCod3r\Spotify\Actions\BaseAction;
use BjTheCod3r\Spotify\Actions\Concerns\HasPagination;
use BjTheCod3r\Spotify\Resources\Artist;
use BjTheCod3r\Spotify\Resources\Paginated;

class GetMyTopArtistsAction extends BaseAction
{
    use HasPagination;

    protected ?string $timeRange = null;

    public function timeRange(?string $range): static
    {
        $this->timeRange = $range;

        return $this;
    }

    protected function path(): string
    {
        return '/me/top/artists';
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
     * @return Paginated<Artist>
     */
    protected function decode(array $payload): Paginated
    {
        return Paginated::fromArray(
            $payload,
            static fn (array $item): Artist => Artist::fromArray($item),
        );
    }

    /**
     * @return Paginated<Artist>
     */
    public function get(): Paginated
    {
        return $this->execute();
    }
}
