<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify;

use BjTheCod3r\Spotify\Auth\ClientCredentialsTokenProvider;
use BjTheCod3r\Spotify\Auth\EloquentUserTokenRepository;
use BjTheCod3r\Spotify\Auth\OAuthManager;
use BjTheCod3r\Spotify\Config\SpotifyConfig;
use BjTheCod3r\Spotify\Contracts\TokenProvider;
use BjTheCod3r\Spotify\Contracts\UserTokenRepository;
use BjTheCod3r\Spotify\Http\SpotifyClient;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
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

        $this->app->singleton(UserTokenRepository::class, function ($app): UserTokenRepository {
            $config = $app->make(SpotifyConfig::class);
            $class = $config->oauth->tokenRepository;

            if ($class !== null && $class !== '') {
                return $app->make($class);
            }

            return $app->make(EloquentUserTokenRepository::class);
        });

        $this->app->singleton(OAuthManager::class, fn ($app): OAuthManager => new OAuthManager(
            config: $app->make(SpotifyConfig::class),
            repository: $app->make(UserTokenRepository::class),
            auth: $app->make(AuthFactory::class),
            cache: $app->make(CacheFactory::class),
            events: $app->make(Dispatcher::class),
        ));

        $this->app->singleton(SpotifyClient::class, fn ($app): SpotifyClient => new SpotifyClient(
            tokenProvider: $app->make(TokenProvider::class),
            config: $app->make(SpotifyConfig::class),
        ));

        $this->app->singleton(Spotify::class, fn ($app): Spotify => new Spotify(
            client: $app->make(SpotifyClient::class),
            config: $app->make(SpotifyConfig::class),
            oauth: $app->make(OAuthManager::class),
        ));
        $this->app->alias(Spotify::class, 'spotify');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/spotify.php' => $this->app->configPath('spotify.php'),
            ], 'spotify-config');

            $this->publishesMigrations([
                __DIR__.'/../database/migrations' => $this->app->databasePath('migrations'),
            ], 'spotify-migrations');
        }

        $config = $this->app->make(SpotifyConfig::class);

        if ($config->oauth->routesEnabled) {
            Route::middleware($config->oauth->routesMiddleware)
                ->prefix($config->oauth->routesPrefix)
                ->group(__DIR__.'/../routes/spotify.php');
        }
    }
}
