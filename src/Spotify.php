<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify;

use BjTheCod3r\Spotify\Actions\Albums\GetAlbumAction;
use BjTheCod3r\Spotify\Actions\Artists\GetArtistAction;
use BjTheCod3r\Spotify\Actions\Audiobooks\GetAudiobookAction;
use BjTheCod3r\Spotify\Actions\Episodes\GetEpisodeAction;
use BjTheCod3r\Spotify\Actions\Playlists\GetPlaylistAction;
use BjTheCod3r\Spotify\Actions\Search\SearchAction;
use BjTheCod3r\Spotify\Actions\Search\SearchAlbumsAction;
use BjTheCod3r\Spotify\Actions\Search\SearchArtistsAction;
use BjTheCod3r\Spotify\Actions\Search\SearchAudiobooksAction;
use BjTheCod3r\Spotify\Actions\Search\SearchEpisodesAction;
use BjTheCod3r\Spotify\Actions\Search\SearchPlaylistsAction;
use BjTheCod3r\Spotify\Actions\Search\SearchShowsAction;
use BjTheCod3r\Spotify\Actions\Search\SearchTracksAction;
use BjTheCod3r\Spotify\Actions\Shows\GetShowAction;
use BjTheCod3r\Spotify\Actions\Tracks\GetTrackAction;
use BjTheCod3r\Spotify\Actions\Users\GetUserAction;
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

    public function playlist(string $id): GetPlaylistAction
    {
        return (new GetPlaylistAction($this->client))->id($id);
    }

    public function album(string $id): GetAlbumAction
    {
        return (new GetAlbumAction($this->client))->id($id);
    }

    public function artist(string $id): GetArtistAction
    {
        return (new GetArtistAction($this->client))->id($id);
    }

    public function track(string $id): GetTrackAction
    {
        return (new GetTrackAction($this->client))->id($id);
    }

    public function show(string $id): GetShowAction
    {
        return (new GetShowAction($this->client))->id($id);
    }

    public function episode(string $id): GetEpisodeAction
    {
        return (new GetEpisodeAction($this->client))->id($id);
    }

    public function audiobook(string $id): GetAudiobookAction
    {
        return (new GetAudiobookAction($this->client))->id($id);
    }

    public function user(string $id): GetUserAction
    {
        return (new GetUserAction($this->client))->id($id);
    }
}
