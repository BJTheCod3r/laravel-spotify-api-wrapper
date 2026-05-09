<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify;

use BjTheCod3r\Spotify\Actions\Search\SearchAction;
use BjTheCod3r\Spotify\Actions\Search\SearchAlbumsAction;
use BjTheCod3r\Spotify\Actions\Search\SearchArtistsAction;
use BjTheCod3r\Spotify\Actions\Search\SearchAudiobooksAction;
use BjTheCod3r\Spotify\Actions\Search\SearchEpisodesAction;
use BjTheCod3r\Spotify\Actions\Search\SearchPlaylistsAction;
use BjTheCod3r\Spotify\Actions\Search\SearchShowsAction;
use BjTheCod3r\Spotify\Actions\Search\SearchTracksAction;
use BjTheCod3r\Spotify\Config\SpotifyConfig;
use BjTheCod3r\Spotify\Enums\SearchType;
use BjTheCod3r\Spotify\Http\SpotifyClient;

/**
 * Entry point for the package. Returns fluent Action instances which the
 * caller configures further (->market(), ->limit(), etc.) before calling
 * ->get() / ->execute().
 *
 * The class itself is intentionally thin — actions are the unit of work,
 * and this manager exists only to give the public API a single discoverable
 * surface (and a Facade).
 */
class Spotify
{
    public function __construct(
        protected SpotifyClient $client,
        protected SpotifyConfig $config,
    ) {
    }

    /**
     * Multi-type search. `$types` may be SearchType enums or their string
     * values; pass an empty array to set them later via ->types().
     *
     * @param array<int, SearchType|string> $types
     */
    public function search(string $query, array $types = []): SearchAction
    {
        $action = (new SearchAction($this->client, $this->config))->q($query);

        return $types === [] ? $action : $action->types($types);
    }

    public function searchTracks(string $query): SearchTracksAction
    {
        return (new SearchTracksAction($this->client, $this->config))->q($query);
    }

    public function searchAlbums(string $query): SearchAlbumsAction
    {
        return (new SearchAlbumsAction($this->client, $this->config))->q($query);
    }

    public function searchArtists(string $query): SearchArtistsAction
    {
        return (new SearchArtistsAction($this->client, $this->config))->q($query);
    }

    public function searchPlaylists(string $query): SearchPlaylistsAction
    {
        return (new SearchPlaylistsAction($this->client, $this->config))->q($query);
    }

    public function searchShows(string $query): SearchShowsAction
    {
        return (new SearchShowsAction($this->client, $this->config))->q($query);
    }

    public function searchEpisodes(string $query): SearchEpisodesAction
    {
        return (new SearchEpisodesAction($this->client, $this->config))->q($query);
    }

    public function searchAudiobooks(string $query): SearchAudiobooksAction
    {
        return (new SearchAudiobooksAction($this->client, $this->config))->q($query);
    }
}
