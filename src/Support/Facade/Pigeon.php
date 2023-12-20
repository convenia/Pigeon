<?php

namespace Convenia\Pigeon\Support\Facade;

use Convenia\Pigeon\Support\Testing\PigeonFake;
use Illuminate\Support\Facades\Facade;

/**
 * Class Pigeon.
 *
 * @method static \Convenia\Pigeon\Drivers\Driver driver(string $driver)
 * @method static \Convenia\Pigeon\Contracts\Publisher routing(string $name = null)
 * @method static \Convenia\Pigeon\Contracts\Publisher exchange(string $name, string $type = 'direct')
 * @method static \Convenia\Pigeon\Contracts\Publisher dispatch(string $eventName, array $event, array $meta = [])
 * @method static \Convenia\Pigeon\Contracts\Consumer queue(string $name, array $properties = [])
 * @method static \Convenia\Pigeon\Contracts\Consumer events(string $name = '#')
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
     * @return string
     *
     * @throws \$resp_queueRuntimeException
     */
    protected static function getFacadeAccessor(): string
    {
        return 'pigeon';
    }
}
