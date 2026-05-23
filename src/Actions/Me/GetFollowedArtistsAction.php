<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Me;

use BjTheCod3r\Spotify\Actions\BaseAction;
use BjTheCod3r\Spotify\Actions\Concerns\HasPagination;
use BjTheCod3r\Spotify\Resources\Artist;
use BjTheCod3r\Spotify\Resources\Paginated;

class GetFollowedArtistsAction extends BaseAction
{
    use HasPagination;

    protected ?string $after = null;

    /**
     * Artist id to start cursoring after.
     */
    public function after(?string $artistId): static
    {
        $this->after = $artistId;

        return $this;
    }

    protected function path(): string
    {
        return '/me/following';
    }

    /**
     * @return array<string, mixed>
     */
    protected function query(): array
    {
        return [
            'type' => 'artist',
            'limit' => $this->limit,
            'after' => $this->after,
        ];
    }

    /**
     * Spotify nests the page inside `{ artists: {...} }`.
     *
     * @param array<string, mixed> $payload
     *
     * @return Paginated<Artist>
     */
    protected function decode(array $payload): Paginated
    {
        $artists = $payload['artists'] ?? [];
        $artists = is_array($artists) ? $artists : [];

        return Paginated::fromArray(
            $artists,
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
