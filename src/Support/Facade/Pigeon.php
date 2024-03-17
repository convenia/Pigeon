<?php

namespace Convenia\Pigeon\Support\Facade;

use Convenia\Pigeon\Support\Testing\PigeonFake;
use Illuminate\Support\Facades\Facade;

/**
 * Class Pigeon.
 *
 * @method static \Convenia\Pigeon\Contracts\Consumer events(string $name = '#')
 * @method static \Convenia\Pigeon\Contracts\Consumer queue(string $name, array $properties = [])
 * @method static \Convenia\Pigeon\Contracts\Publisher dispatch(string $eventName, array $event, array $meta = [])
 * @method static \Convenia\Pigeon\Contracts\Publisher exchange(string $name, string $type = 'direct')
 * @method static \Convenia\Pigeon\Contracts\Publisher routing(string $name = null)
 * @method static void assertConsuming(string $queue, int $timeout = null, bool $multiple = null)
 * @method static void assertConsumingEvent(string $event, int $timeout = null, bool $multiple = null)
 * @method static void assertDispatched(string $category, array $data)
 * @method static void assertNotDispatched(string $category, array $data)
 * @method static void assertPublished(string $routing, array $message)
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
