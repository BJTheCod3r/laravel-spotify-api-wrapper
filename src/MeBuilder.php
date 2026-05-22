<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify;

use BjTheCod3r\Spotify\Actions\Me\GetCurrentUserProfileAction;
use BjTheCod3r\Spotify\Actions\Me\GetFollowedArtistsAction;
use BjTheCod3r\Spotify\Actions\Me\GetMyPlaylistsAction;
use BjTheCod3r\Spotify\Actions\Me\GetMyRecentlyPlayedAction;
use BjTheCod3r\Spotify\Actions\Me\GetMySavedAlbumsAction;
use BjTheCod3r\Spotify\Actions\Me\GetMySavedAudiobooksAction;
use BjTheCod3r\Spotify\Actions\Me\GetMySavedEpisodesAction;
use BjTheCod3r\Spotify\Actions\Me\GetMySavedShowsAction;
use BjTheCod3r\Spotify\Actions\Me\GetMySavedTracksAction;
use BjTheCod3r\Spotify\Actions\Me\GetMyTopArtistsAction;
use BjTheCod3r\Spotify\Actions\Me\GetMyTopTracksAction;
use BjTheCod3r\Spotify\Http\SpotifyClient;

/**
 * Fluent surface for `/me/*` endpoints — methods return Action builders the
 * caller terminates with ->get(). Always backed by a user-context client.
 */
final class MeBuilder
{
    public function __construct(private readonly SpotifyClient $client)
    {
    }

    public function profile(): GetCurrentUserProfileAction
    {
        return new GetCurrentUserProfileAction($this->client);
    }

    public function playlists(): GetMyPlaylistsAction
    {
        return new GetMyPlaylistsAction($this->client);
    }

    public function savedTracks(): GetMySavedTracksAction
    {
        return new GetMySavedTracksAction($this->client);
    }

    public function savedAlbums(): GetMySavedAlbumsAction
    {
        return new GetMySavedAlbumsAction($this->client);
    }

    public function savedShows(): GetMySavedShowsAction
    {
        return new GetMySavedShowsAction($this->client);
    }

    public function savedEpisodes(): GetMySavedEpisodesAction
    {
        return new GetMySavedEpisodesAction($this->client);
    }

    public function savedAudiobooks(): GetMySavedAudiobooksAction
    {
        return new GetMySavedAudiobooksAction($this->client);
    }

    public function topTracks(): GetMyTopTracksAction
    {
        return new GetMyTopTracksAction($this->client);
    }

    public function topArtists(): GetMyTopArtistsAction
    {
        return new GetMyTopArtistsAction($this->client);
    }

    public function recentlyPlayed(): GetMyRecentlyPlayedAction
    {
        return new GetMyRecentlyPlayedAction($this->client);
    }

    public function followedArtists(): GetFollowedArtistsAction
    {
        return new GetFollowedArtistsAction($this->client);
    }
}
