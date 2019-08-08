<?php

namespace Convenia\AMQP;

use Illuminate\Support\ServiceProvider;

/**
 * Class AMQPServiceProvider.
 */
class AMQPServiceProvider extends ServiceProvider
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
        $this->app->singleton('amqp', static function ($app) {
            return new AMQPManager($app);
        });

        $this->mergeConfigFrom(
            __DIR__.'/../config/amqp.php',
            'amqp'
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return ['amqp'];
    }
}
