<?php

namespace Convenia\Pigeon;

use BadMethodCallException;
use Convenia\Pigeon\BridgeManager as Manager;
use Convenia\Pigeon\Drivers\RabbitDriver;
use Convenia\Pigeon\Exceptions\Driver\NullDriverException;

/**
 * Class PigeonManager.
 */
class PigeonManager extends Manager
{
    public function headers(array $headers)
    {
        $old = $this->app['config']->get($key = 'pigeon.headers');
        $this->app['config']->set($key, array_merge($old, $headers));
    }

    public function createRabbitDriver()
    {
        return new RabbitDriver($this->app);
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
        return $this->app['config']['pigeon.default'] ?? 'null';
    }

    /**
     * Dynamically pass calls to the default connection.
     *
     * @param  string  $method
     * @param  array  $parameters
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
