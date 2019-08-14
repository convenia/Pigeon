<?php

namespace Convenia\Pigeon\MessageProcessor;

use Closure;
use PhpAmqpLib\Message\AMQPMessage;
use Convenia\Pigeon\Drivers\DriverContract;

interface MessageProcessorContract
{
    public function __construct(DriverContract $driver, Closure $callback, Closure $fallback = null);

    public function process(AMQPMessage $message);
}
