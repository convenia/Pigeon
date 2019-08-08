<?php

namespace Convenia\AMQP;

use Convenia\AMQP\Drivers\Driver;
use Convenia\AMQP\Drivers\NullDriver;
use Convenia\AMQP\Drivers\RabbitDriver;
use Illuminate\Support\Manager;
use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * Class AMQPManager.
 */
class AMQPManager extends Manager
{
    /**
     * Create Rabbit Driver.
     *
     * @return \Convenia\AMQP\Drivers\Driver
     */
    public function getRabbitDriver(): Driver
    {
        return new RabbitDriver(
            $this->app,
            $this->createAmqpConnection()
        );
    }

    /**
     * Create a Null Amqp driver instance.
     *
     * @return \Convenia\AMQP\Drivers\NullDriver
     */
    public function createNullDriver(): NullDriver
    {
        return new NullDriver($this->app);
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return $this->app['config']['amqp.default'] ?? 'null';
    }

    /**
     * @return array connection attributes
     */
    private function getConnectionAttributes(): array
    {
        return [
            $this->app['config']['amqp.connection.host.address'],
            $this->app['config']['amqp.connection.host.port'],
            $this->app['config']['amqp.connection.credentials.user'],
            $this->app['config']['amqp.connection.credentials.password'],
            $this->app['config']['amqp.connection.host.vhost'],
            $insist = false,
            $login_method = 'AMQPLAIN',
            $login_response = null,
            $locale = 'en_US',
            $this->app['config']['amqp.connection.read_timeout'],
            $this->app['config']['amqp.connection.keepalive'],
            $this->app['config']['amqp.connection.write_timeout'],
            (int) $this->app['config']['amqp.connection.heartbeat'],
            $channel_rpc_timeout = 60,
        ];
    }

    /**
     * Create AMQP Connection.
     *
     * @return \PhpAmqpLib\Connection\AMQPStreamConnection
     */
    protected function createAmqpConnection(): AMQPStreamConnection
    {
        return new AMQPStreamConnection(...$this->getConnectionAttributes());
    }
}
