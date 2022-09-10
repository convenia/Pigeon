<?php

namespace Convenia\Pigeon;

use Illuminate\Support\ServiceProvider;
use PhpAmqpLib\Connection\AMQPStreamConnection;

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

        $this->app->singleton(AMQPStreamConnection::class, function ($app) {
            $configs = $app['config']['pigeon.connection'];

            return new AMQPStreamConnection(
                data_get($configs, 'host.address'),
                data_get($configs, 'host.port'),
                data_get($configs, 'credentials.user'),
                data_get($configs, 'credentials.password'),
                data_get($configs, 'host.vhost'),
                false,
                'AMQPLAIN',
                null,
                'en_US',
                3.0,
                data_get($configs, 'read_timeout'),
                null,
                data_get($configs, 'keepalive'),
                data_get($configs, 'heartbeat')
            );
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
        return __DIR__ . '/../config/pigeon.php';
    }
}
