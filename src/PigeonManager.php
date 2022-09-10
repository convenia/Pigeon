<?php

namespace Convenia\Pigeon;

use Convenia\Pigeon\Drivers\RabbitDriver;
use Illuminate\Support\Manager;

/**
 * Class PigeonManager.
 */
class PigeonManager extends Manager
{
    public function headers(array $headers)
    {
        $old = $this->config->get($key = 'pigeon.headers');

        $this->config->set($key, array_merge($old, $headers));
    }

    public function createRabbitDriver()
    {
        return new RabbitDriver($this->container);
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
