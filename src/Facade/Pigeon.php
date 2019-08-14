<?php

namespace Convenia\Pigeon\Facade;

use Convenia\Pigeon\Consumer\ConsumerContract;
use Convenia\Pigeon\Support\Testing\PigeonFake;
use Illuminate\Support\Facades\Facade;

/**
 * Class Pigeon.
 *
 * @method static ConsumerContract queue(string $name)
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
