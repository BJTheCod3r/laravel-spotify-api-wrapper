<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Artists;

use BjTheCod3r\Spotify\Actions\BaseAction;
use BjTheCod3r\Spotify\Actions\Concerns\HasMarket;
use BjTheCod3r\Spotify\Config\SpotifyConfig;
use BjTheCod3r\Spotify\Http\SpotifyClient;
use BjTheCod3r\Spotify\Resources\Track;
use Illuminate\Support\Collection;

class GetArtistTopTracksAction extends BaseAction
{
    use HasMarket;

    /**
     * Unlike the other Get-by-ID actions, this one seeds `spotify.defaults.market`:
     * without a market (or a user token to infer one from) Spotify considers the
     * catalogue unavailable and returns nothing at all.
     */
    public function __construct(SpotifyClient $client, SpotifyConfig $config)
    {
        parent::__construct($client);

        $this->market = $config->defaultMarket;
    }

    public function id(string $id): static
    {
        $this->pathParameters['id'] = $id;

        return $this;
    }

    protected function path(): string
    {
        return '/artists/{id}/top-tracks';
    }

    /**
     * @return array<string, mixed>
     */
    protected function query(): array
    {
        return [
            'market' => $this->market,
        ];
    }

    /**
     * The endpoint returns a bare `tracks` list (up to 10) rather than a
     * paging object, so there is nothing to page through.
     *
     * @param array<string, mixed> $payload
     *
     * @return Collection<int, Track>
     */
    protected function decode(array $payload): Collection
    {
        return Track::collection($payload['tracks'] ?? []);
    }

    /**
     * @return Collection<int, Track>
     */
    public function get(): Collection
    {
        return $this->execute();
    }
}
