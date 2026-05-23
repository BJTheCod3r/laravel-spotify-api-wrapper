<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Tests;

use BjTheCod3r\Spotify\SpotifyServiceProvider;
use BjTheCod3r\Spotify\Tests\Stubs\TestUser;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            SpotifyServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        $app['config']->set('spotify.client_id', 'test-client-id');
        $app['config']->set('spotify.client_secret', 'test-client-secret');
        $app['config']->set('spotify.endpoints.accounts', 'https://accounts.spotify.com');
        $app['config']->set('spotify.endpoints.api', 'https://api.spotify.com/v1');
        $app['config']->set('spotify.cache.store', 'array');
        $app['config']->set('cache.default', 'array');

        $app['config']->set('spotify.oauth.redirect_uri', 'https://app.test/spotify/callback');
        $app['config']->set('spotify.oauth.routes.middleware', ['web']);

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('auth.providers.users.driver', 'eloquent');
        $app['config']->set('auth.providers.users.model', TestUser::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
