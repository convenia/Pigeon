<?php

namespace Convenia\Pigeon;

use BadMethodCallException;
use Convenia\Pigeon\Drivers\RabbitDriver;
use Convenia\Pigeon\Exceptions\Driver\NullDriverException;
use Illuminate\Support\Manager;

/**
 * Class PigeonManager.
 */
class PigeonManager extends Manager
{
    public function headers(array $headers)
    {
        $old = $this->container['config']->get($key = 'pigeon.headers');
        $this->container['config']->set($key, array_merge($old, $headers));
    }

    public function createRabbitDriver()
    {
        return new RabbitDriver($this->container);
    }

    public function createNullDriver()
    {
        throw new NullDriverException();
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return $this->container['config']['pigeon.default'] ?? 'null';
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
