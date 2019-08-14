<?php

namespace Convenia\Pigeon;

use Illuminate\Support\ServiceProvider;

/**
 * Class PigeonServiceProvider.
 */
class PigeonServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('pigeon', static function ($app) {
            return new PigeonManager($app);
        });

        $this->mergeConfigFrom(
            __DIR__.'/../config/pigeon.php',
            'pigeon'
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return ['pigeon'];
    }
}
