<?php

namespace Convenia\Pigeon;

use Illuminate\Support\ServiceProvider;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class PigeonServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            $this->configPath() => config_path('pigeon.php'),
        ], 'pigeon.config');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('pigeon', static function ($app) {
            return new PigeonManager($app);
        });

        $this->app->singleton(AMQPStreamConnection::class, function ($app) {
            $configs = $app['config']['pigeon.connection'];

            return new AMQPStreamConnection(
                host: data_get($configs, 'host.address'),
                port: data_get($configs, 'host.port'),
                user: data_get($configs, 'credentials.user'),
                password: data_get($configs, 'credentials.password'),
                vhost: data_get($configs, 'host.vhost'),
                read_write_timeout: data_get($configs, 'read_timeout'),
                keepalive: data_get($configs, 'keepalive'),
                heartbeat: data_get($configs, 'heartbeat')
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

    /**
     * Pigeon's configurations file path.
     *
     * @return string
     */
    protected function configPath(): string
    {
        return __DIR__ . '/../config/pigeon.php';
    }
}
