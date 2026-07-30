<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Facades;

use BjTheCod3r\Spotify\Actions\Albums\GetAlbumAction;
use BjTheCod3r\Spotify\Actions\Albums\GetAlbumTracksAction;
use BjTheCod3r\Spotify\Actions\Artists\GetArtistAction;
use BjTheCod3r\Spotify\Actions\Artists\GetArtistTopTracksAction;
use BjTheCod3r\Spotify\Actions\Audiobooks\GetAudiobookAction;
use BjTheCod3r\Spotify\Actions\Episodes\GetEpisodeAction;
use BjTheCod3r\Spotify\Actions\Playlists\AddPlaylistItemsAction;
use BjTheCod3r\Spotify\Actions\Playlists\ChangePlaylistDetailsAction;
use BjTheCod3r\Spotify\Actions\Playlists\CreatePlaylistAction;
use BjTheCod3r\Spotify\Actions\Playlists\GetPlaylistAction;
use BjTheCod3r\Spotify\Actions\Playlists\GetPlaylistItemsAction;
use BjTheCod3r\Spotify\Actions\Playlists\RemovePlaylistItemsAction;
use BjTheCod3r\Spotify\Actions\Playlists\ReorderPlaylistItemsAction;
use BjTheCod3r\Spotify\Actions\Playlists\ReplacePlaylistItemsAction;
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
use BjTheCod3r\Spotify\Spotify as SpotifyManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static SearchAction         search(string $query, array $types = [])
 * @method static SearchTracksAction   searchTracks(string $query)
 * @method static SearchAlbumsAction   searchAlbums(string $query)
 * @method static SearchArtistsAction  searchArtists(string $query)
 * @method static SearchPlaylistsAction searchPlaylists(string $query)
 * @method static SearchShowsAction    searchShows(string $query)
 * @method static SearchEpisodesAction searchEpisodes(string $query)
 * @method static SearchAudiobooksAction searchAudiobooks(string $query)
 * @method static GetPlaylistAction    playlist(string $id)
 * @method static GetPlaylistItemsAction playlistItems(string $id)
 * @method static CreatePlaylistAction createPlaylist(string $name)
 * @method static ChangePlaylistDetailsAction updatePlaylist(string $id)
 * @method static AddPlaylistItemsAction addPlaylistItems(string $id, array|string $uris = [])
 * @method static ReplacePlaylistItemsAction replacePlaylistItems(string $id, array|string $uris = [])
 * @method static ReorderPlaylistItemsAction reorderPlaylistItems(string $id)
 * @method static RemovePlaylistItemsAction removePlaylistItems(string $id, array|string $uris = [])
 * @method static GetAlbumAction       album(string $id)
 * @method static GetAlbumTracksAction albumTracks(string $id)
 * @method static GetArtistAction      artist(string $id)
 * @method static GetArtistTopTracksAction artistTopTracks(string $id)
 * @method static GetTrackAction       track(string $id)
 * @method static GetShowAction        show(string $id)
 * @method static GetEpisodeAction     episode(string $id)
 * @method static GetAudiobookAction   audiobook(string $id)
 * @method static GetUserAction        user(string $id)
 *
 * @see SpotifyManager
 */
class Spotify extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SpotifyManager::class;
    }
}
