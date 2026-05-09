<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Tests;

use BjTheCod3r\Spotify\SpotifyServiceProvider;
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
        $app['config']->set('spotify.client_id', 'test-client-id');
        $app['config']->set('spotify.client_secret', 'test-client-secret');
        $app['config']->set('spotify.endpoints.accounts', 'https://accounts.spotify.com');
        $app['config']->set('spotify.endpoints.api', 'https://api.spotify.com/v1');
        $app['config']->set('spotify.cache.store', 'array');
        $app['config']->set('cache.default', 'array');
    }
}
