<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions;

use BjTheCod3r\Spotify\Contracts\Action;
use BjTheCod3r\Spotify\Exceptions\ValidationException;
use BjTheCod3r\Spotify\Http\SpotifyClient;

/**
 * Base for every Spotify endpoint action.
 *
 * Subclasses describe their endpoint by overriding the small set of hooks
 * below — they shouldn't need to touch `execute()` itself.
 *
 * Required:
 *   - {@see path()}            The URL template, e.g. `/albums/{id}`. Tokens
 *                               in `{braces}` are interpolated from
 *                               `$pathParameters` at request time.
 *
 * Optional (all have sensible defaults):
 *   - {@see method()}          HTTP verb. Defaults to GET.
 *   - {@see query()}           Query string parameters. Defaults to none.
 *   - {@see body()}            Request body for POST/PUT/DELETE. Defaults
 *                               to none.
 *   - {@see validate()}        Throw {@see ValidationException} for missing
 *                               required inputs. Runs before the request.
 *   - {@see decode()}          Transform the decoded JSON payload into the
 *                               action's return type. Defaults to passthrough.
 *
 * Path parameters: store them on `$pathParameters` via fluent setters
 * (e.g. `public function id(string $id): static`) so `buildPath()` can
 * substitute them.
 */
abstract class BaseAction implements Action
{
    /** @var array<string, string> */
    protected array $pathParameters = [];

    public function __construct(protected SpotifyClient $client)
    {
    }

    public function execute(): mixed
    {
        $this->validate();

        $payload = match ($method = strtoupper($this->method())) {
            'GET' => $this->client->get($this->buildPath(), $this->query()),
            'POST' => $this->client->post($this->buildPath(), $this->body(), $this->query()),
            'PUT' => $this->client->put($this->buildPath(), $this->body(), $this->query()),
            'DELETE' => $this->client->delete($this->buildPath(), $this->body(), $this->query()),
            default => throw new ValidationException("Unsupported HTTP method: {$method}"),
        };

        return $this->decode($payload);
    }

    /**
     * Fluent terminator. Alias of execute().
     */
    public function get(): mixed
    {
        return $this->execute();
    }

    /**
     * URL template for this endpoint. May contain `{name}` tokens that
     * will be interpolated from `$pathParameters`.
     */
    abstract protected function path(): string;

    protected function method(): string
    {
        return 'GET';
    }

    /**
     * @return array<string, mixed>
     */
    protected function query(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function body(): array
    {
        return [];
    }

    /**
     * Override to enforce required inputs. Throw {@see ValidationException}
     * when something the endpoint requires is missing.
     */
    protected function validate(): void
    {
    }

    /**
     * Transform the raw decoded JSON into this action's return type.
     * Default: pass through unchanged.
     *
     * @param array<string, mixed> $payload
     */
    protected function decode(array $payload): mixed
    {
        return $payload;
    }

    protected function buildPath(): string
    {
        return preg_replace_callback(
            '/\{(\w+)\}/',
            function (array $matches): string {
                $key = $matches[1];

                if (! isset($this->pathParameters[$key])) {
                    throw new ValidationException("Missing required path parameter: {$key}.");
                }

                return rawurlencode($this->pathParameters[$key]);
            },
            $this->path(),
        ) ?? $this->path();
    }
}
