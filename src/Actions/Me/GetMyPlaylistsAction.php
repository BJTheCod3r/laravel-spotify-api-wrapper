<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Me;

use BjTheCod3r\Spotify\Actions\BaseAction;
use BjTheCod3r\Spotify\Actions\Concerns\HasPagination;
use BjTheCod3r\Spotify\Resources\Paginated;
use BjTheCod3r\Spotify\Resources\SimplifiedPlaylist;

class GetMyPlaylistsAction extends BaseAction
{
    use HasPagination;

    protected function path(): string
    {
        return '/me/playlists';
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
     * @return Paginated<SimplifiedPlaylist>
     */
    protected function decode(array $payload): Paginated
    {
        return Paginated::fromArray(
            $payload,
            static fn (array $item): SimplifiedPlaylist => SimplifiedPlaylist::fromArray($item),
        );
    }

    /**
     * @return Paginated<SimplifiedPlaylist>
     */
    public function get(): Paginated
    {
        return $this->execute();
    }
}
