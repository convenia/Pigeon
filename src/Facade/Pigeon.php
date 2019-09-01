<?php

namespace Convenia\Pigeon\Facade;

use Illuminate\Support\Facades\Facade;
use Convenia\Pigeon\Support\Testing\PigeonFake;

/**
 * Class Pigeon.
 *
 * @method static \Convenia\Pigeon\Drivers\Driver driver(string $driver)
 * @method static \Convenia\Pigeon\Publisher\PublisherContract routing(string $name = null)
 * @method static \Convenia\Pigeon\Publisher\PublisherContract exchange(string $name, string $type = 'direct')
 * @method static \Convenia\Pigeon\Publisher\PublisherContract emmit(string $event, array $data)
 * @method static \Convenia\Pigeon\Consumer\ConsumerContract queue(string $name, array $properties = [])
 * @method static \Convenia\Pigeon\Consumer\ConsumerContract events(string $name = '#')
 */
class Pigeon extends Facade
{
    public static function fake(): PigeonFake
    {
        static::swap(new PigeonFake(static::$app));

        return self::getFacadeRoot();
    }

    /**
     * Get the registered name of the component.
     *
     * @throws \$resp_queueRuntimeException
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'pigeon';
    }
}
