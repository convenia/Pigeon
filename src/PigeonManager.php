<?php

namespace Convenia\Pigeon;

use Convenia\Pigeon\Drivers\RabbitMQDriver;
use Illuminate\Support\Manager;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class PigeonManager extends Manager
{
    public function headers(array $headers)
    {
        $old = $this->config->get($key = 'pigeon.headers');

        $this->config->set($key, array_merge($old, $headers));
    }

    /**
     * Creates the driver for RabbitMQ.
     *
     * @return \Convenia\Pigeon\Drivers\RabbitMQDriver
     */
    public function createRabbitmqDriver(): RabbitMQDriver
    {
        return new RabbitMQDriver(
            $this->container,
            $this->container->make(AMQPStreamConnection::class)
        );
    }

    /**
     * Get the default driver name.
     *
     * @return string|null
     */
    public function getDefaultDriver(): ?string
    {
        return $this->config['pigeon.default'];
    }
}
