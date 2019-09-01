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

    public function boot()
    {
        $this->publishes([
            $this->configPath() => config_path('pigeon.php'),
        ], 'pigeon.config');
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('pigeon', static function ($app) {
            return new PigeonManager($app);
        });

        $this->mergeConfigFrom(
            $this->configPath(),
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

    private function configPath()
    {
        return __DIR__.'/../config/pigeon.php';
    }
}
