<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify;

use BjTheCod3r\Spotify\Auth\ClientCredentialsTokenProvider;
use BjTheCod3r\Spotify\Config\SpotifyConfig;
use BjTheCod3r\Spotify\Contracts\TokenProvider;
use BjTheCod3r\Spotify\Http\SpotifyClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class SpotifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/spotify.php', 'spotify');

        $this->app->singleton(SpotifyConfig::class, function ($app): SpotifyConfig {
            /** @var array<string, mixed> $config */
            $config = $app['config']->get('spotify', []);

            return SpotifyConfig::fromArray($config);
        });

        $this->app->singleton(TokenProvider::class, function ($app): TokenProvider {
            $config = $app->make(SpotifyConfig::class);
            $cache = $config->cacheStore ? Cache::store($config->cacheStore) : Cache::store();

            return new ClientCredentialsTokenProvider($config, $cache);
        });

        $this->app->singleton(SpotifyClient::class, fn ($app): SpotifyClient => new SpotifyClient(
            tokenProvider: $app->make(TokenProvider::class),
            config: $app->make(SpotifyConfig::class),
        ));

        $this->app->singleton(Spotify::class, fn ($app): Spotify => new Spotify(
            client: $app->make(SpotifyClient::class),
            config: $app->make(SpotifyConfig::class),
        ));
        $this->app->alias(Spotify::class, 'spotify');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/spotify.php' => $this->app->configPath('spotify.php'),
            ], 'spotify-config');
        }
    }
}
