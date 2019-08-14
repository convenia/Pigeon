<?php

namespace Convenia\Pigeon\MessageProcessor;

use Closure;
use Convenia\Pigeon\Drivers\DriverContract;
use PhpAmqpLib\Message\AMQPMessage;

interface MessageProcessorContract
{
    public function __construct(DriverContract $driver, Closure $callback, Closure $fallback = null);

    public function process(AMQPMessage $message);
}
