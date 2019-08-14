<?php

namespace Convenia\Pigeon;

use BadMethodCallException;
use Illuminate\Support\Manager;
use Convenia\Pigeon\Drivers\RabbitDriver;

/**
 * Class PigeonManager.
 */
class PigeonManager extends Manager
{
    public function createRabbitDriver()
    {
        return new RabbitDriver($this->app);
    }

    public function createNullDriver()
    {
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
     * Dynamically pass calls to the default connection.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (method_exists($this->driver(), $method)) {
            return $this->driver()->$method(...$parameters);
        }

        throw new BadMethodCallException(sprintf(
            'Call to undefined method %s::%s()',
            static::class,
            $method
        ));
    }
}
