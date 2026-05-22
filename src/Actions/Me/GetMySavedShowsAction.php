<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Me;

use BjTheCod3r\Spotify\Actions\BaseAction;
use BjTheCod3r\Spotify\Actions\Concerns\HasPagination;
use BjTheCod3r\Spotify\Resources\Paginated;
use BjTheCod3r\Spotify\Resources\Show;

class GetMySavedShowsAction extends BaseAction
{
    use HasPagination;

    protected function path(): string
    {
        return '/me/shows';
    }

    /**
     * @return array<string, mixed>
     */
    protected function query(): array
    {
        return [
            'limit' => $this->limit,
            'offset' => $this->offset,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return Paginated<Show>
     */
    protected function decode(array $payload): Paginated
    {
        return Paginated::fromArray(
            $payload,
            static function (array $item): Show {
                $show = $item['show'] ?? [];

                return Show::fromArray(is_array($show) ? $show : []);
            },
        );
    }

    /**
     * @return Paginated<Show>
     */
    public function get(): Paginated
    {
        return $this->execute();
    }
}
