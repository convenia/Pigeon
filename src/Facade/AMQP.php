<?php

namespace Convenia\AMQP\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * Class AMQP.
 */
class AMQP extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected static function getFacadeAccessor(): string
    {
        return 'amqp';
    }
}
