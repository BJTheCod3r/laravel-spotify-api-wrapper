<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Playlists;

use BjTheCod3r\Spotify\Actions\BaseAction;
use BjTheCod3r\Spotify\Exceptions\ValidationException;
use BjTheCod3r\Spotify\Resources\Playlist;

/**
 * Creates an empty playlist on a user's account. The owner defaults to the
 * connected user, and {@see forUser()} overrides it. Add tracks afterwards
 * with {@see AddPlaylistItemsAction}; Spotify has no create-with-items
 * endpoint.
 */
class CreatePlaylistAction extends BaseAction
{
    protected ?string $name = null;

    protected ?string $description = null;

    protected ?bool $public = null;

    protected ?bool $collaborative = null;

    /** @var (callable(): string)|null */
    protected $ownerResolver = null;

    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function description(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function public(bool $public = true): static
    {
        $this->public = $public;

        return $this;
    }

    public function collaborative(bool $collaborative = true): static
    {
        $this->collaborative = $collaborative;

        return $this;
    }

    /**
     * Create the playlist on a specific Spotify account, by Spotify user id.
     */
    public function forUser(string $spotifyUserId): static
    {
        $this->pathParameters['user_id'] = $spotifyUserId;

        return $this;
    }

    /**
     * Defer resolving the owner until the request is made, so constructing
     * the action doesn't cost a repository lookup.
     *
     * @param callable(): string $resolver
     */
    public function resolveOwnerUsing(callable $resolver): static
    {
        $this->ownerResolver = $resolver;

        return $this;
    }

    protected function path(): string
    {
        return '/users/{user_id}/playlists';
    }

    protected function method(): string
    {
        return 'POST';
    }

    /**
     * @return array<string, mixed>
     */
    protected function body(): array
    {
        return array_filter([
            'name' => $this->name,
            'description' => $this->description,
            'public' => $this->public,
            'collaborative' => $this->collaborative,
        ], static fn (mixed $value): bool => $value !== null);
    }

    protected function validate(): void
    {
        if ($this->name === null || trim($this->name) === '') {
            throw new ValidationException('A playlist name is required.');
        }

        if ($this->collaborative === true && $this->public === true) {
            throw new ValidationException('A collaborative playlist cannot be public. Call ->public(false) alongside ->collaborative().');
        }

        if (isset($this->pathParameters['user_id'])) {
            return;
        }

        if ($this->ownerResolver === null) {
            throw new ValidationException('No playlist owner resolved. Call ->forUser($spotifyUserId).');
        }

        $this->pathParameters['user_id'] = ($this->ownerResolver)();
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function decode(array $payload): Playlist
    {
        return Playlist::fromArray($payload);
    }

    public function get(): Playlist
    {
        return $this->execute();
    }
}
