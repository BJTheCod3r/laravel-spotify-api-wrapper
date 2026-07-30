<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify;

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
use BjTheCod3r\Spotify\Auth\OAuthManager;
use BjTheCod3r\Spotify\Auth\UserTokenSet;
use BjTheCod3r\Spotify\Config\SpotifyConfig;
use BjTheCod3r\Spotify\Enums\SearchType;
use BjTheCod3r\Spotify\Http\SpotifyClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
    protected int|string|null $boundUserId = null;

    public function __construct(
        protected SpotifyClient $client,
        protected SpotifyConfig $config,
        protected OAuthManager $oauth,
    ) {
    }

    /**
     * Return a Spotify manager scoped to the given local user id. The
     * returned instance is independent of the container-bound default
     * (client-credentials) instance — safe to use from concurrent workers.
     */
    public function asUser(int|string $userId): self
    {
        $clone = new self(
            client: $this->oauth->clientFor($userId),
            config: $this->config,
            oauth: $this->oauth,
        );
        $clone->boundUserId = $userId;

        return $clone;
    }

    /**
     * Entry point for `/me/*` endpoints. Uses the user this instance is
     * already bound to (via asUser); otherwise resolves the current user
     * via the configured auth guard.
     */
    public function me(): MeBuilder
    {
        return new MeBuilder($this->userClient());
    }

    /**
     * Build a redirect to Spotify's consent screen, persisting PKCE state
     * in the request's session.
     *
     * @param list<string>|null $scopes Extra scopes to request. Null → defaults.
     * @param bool              $replaceScopes Replace defaults rather than merge.
     */
    public function redirect(Request $request, ?array $scopes = null, bool $replaceScopes = false): RedirectResponse
    {
        return $this->oauth->redirect($request->session(), $scopes, $replaceScopes);
    }

    public function handleCallback(Request $request): UserTokenSet
    {
        return $this->oauth->handleCallback($request);
    }

    public function disconnect(int|string|null $userId = null): void
    {
        $this->oauth->disconnect($userId);
    }

    public function oauth(): OAuthManager
    {
        return $this->oauth;
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

    public function playlistItems(string $id): GetPlaylistItemsAction
    {
        return (new GetPlaylistItemsAction($this->client))->id($id);
    }

    /**
     * Create a playlist on the connected user's account. Requires the
     * `playlist-modify-public` / `playlist-modify-private` scope, and the
     * owner defaults to whichever user this instance acts as.
     */
    public function createPlaylist(string $name): CreatePlaylistAction
    {
        return (new CreatePlaylistAction($this->userClient()))
            ->name($name)
            ->resolveOwnerUsing(fn (): string => $this->oauth->spotifyUserId($this->boundUserId));
    }

    public function updatePlaylist(string $id): ChangePlaylistDetailsAction
    {
        return (new ChangePlaylistDetailsAction($this->userClient()))->id($id);
    }

    /**
     * @param list<string>|string $uris Track URIs, ids, or open.spotify.com links.
     */
    public function addPlaylistItems(string $id, array|string $uris = []): AddPlaylistItemsAction
    {
        return (new AddPlaylistItemsAction($this->userClient()))->id($id)->uris($uris);
    }

    /**
     * @param list<string>|string $uris Replaces the playlist's contents; empty clears it.
     */
    public function replacePlaylistItems(string $id, array|string $uris = []): ReplacePlaylistItemsAction
    {
        return (new ReplacePlaylistItemsAction($this->userClient()))->id($id)->uris($uris);
    }

    public function reorderPlaylistItems(string $id): ReorderPlaylistItemsAction
    {
        return (new ReorderPlaylistItemsAction($this->userClient()))->id($id);
    }

    /**
     * @param list<string>|string $uris Track URIs, ids, or open.spotify.com links.
     */
    public function removePlaylistItems(string $id, array|string $uris = []): RemovePlaylistItemsAction
    {
        return (new RemovePlaylistItemsAction($this->userClient()))->id($id)->uris($uris);
    }

    public function album(string $id): GetAlbumAction
    {
        return (new GetAlbumAction($this->client))->id($id);
    }

    public function albumTracks(string $id): GetAlbumTracksAction
    {
        return (new GetAlbumTracksAction($this->client))->id($id);
    }

    public function artist(string $id): GetArtistAction
    {
        return (new GetArtistAction($this->client))->id($id);
    }

    public function artistTopTracks(string $id): GetArtistTopTracksAction
    {
        return (new GetArtistTopTracksAction($this->client, $this->config))->id($id);
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

    /**
     * Client for endpoints that only work in user context. Uses the user
     * this instance is bound to (via asUser); otherwise resolves the
     * current user via the configured auth guard.
     */
    protected function userClient(): SpotifyClient
    {
        if ($this->boundUserId !== null) {
            return $this->client;
        }

        return $this->oauth->clientFor($this->oauth->currentUserId());
    }
}
