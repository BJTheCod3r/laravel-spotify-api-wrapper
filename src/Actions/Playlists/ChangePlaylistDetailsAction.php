<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Playlists;

use BjTheCod3r\Spotify\Actions\BaseAction;
use BjTheCod3r\Spotify\Exceptions\ValidationException;

/**
 * Renames a playlist or changes its description/visibility. Spotify answers
 * with an empty body, so this returns `true`; any failure surfaces as an
 * exception.
 */
class ChangePlaylistDetailsAction extends BaseAction
{
    protected ?string $name = null;

    protected ?string $description = null;

    protected ?bool $public = null;

    protected ?bool $collaborative = null;

    public function id(string $id): static
    {
        $this->pathParameters['id'] = $id;

        return $this;
    }

    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Pass an empty string to clear an existing description.
     */
    public function description(string $description): static
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

    protected function path(): string
    {
        return '/playlists/{id}';
    }

    protected function method(): string
    {
        return 'PUT';
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
        if ($this->body() === []) {
            throw new ValidationException('Nothing to update. Set at least one of ->name(), ->description(), ->public(), or ->collaborative().');
        }

        if ($this->name !== null && trim($this->name) === '') {
            throw new ValidationException('A playlist name cannot be empty.');
        }

        if ($this->collaborative === true && $this->public === true) {
            throw new ValidationException('A collaborative playlist cannot be public. Call ->public(false) alongside ->collaborative().');
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function decode(array $payload): bool
    {
        return true;
    }

    public function get(): bool
    {
        return $this->execute();
    }
}
