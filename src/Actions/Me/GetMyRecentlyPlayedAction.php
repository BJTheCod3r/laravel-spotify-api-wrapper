<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Me;

use BjTheCod3r\Spotify\Actions\BaseAction;
use BjTheCod3r\Spotify\Actions\Concerns\HasPagination;
use BjTheCod3r\Spotify\Resources\Paginated;
use BjTheCod3r\Spotify\Resources\Track;

class GetMyRecentlyPlayedAction extends BaseAction
{
    use HasPagination;

    protected ?int $after = null;

    protected ?int $before = null;

    /**
     * Unix timestamp in ms; results are tracks played after this instant.
     */
    public function after(?int $milliseconds): static
    {
        $this->after = $milliseconds;

        return $this;
    }

    public function before(?int $milliseconds): static
    {
        $this->before = $milliseconds;

        return $this;
    }

    protected function path(): string
    {
        return '/me/player/recently-played';
    }

    /**
     * @return array<string, mixed>
     */
    protected function query(): array
    {
        return [
            'limit' => $this->limit,
            'after' => $this->after,
            'before' => $this->before,
        ];
    }

    /**
     * Cursor-paginated; items are `{ track, played_at, context }`. The
     * played_at/context wrapper is dropped — only the inner Track is hydrated.
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
