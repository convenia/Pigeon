<?php

namespace Convenia\Pigeon\Contracts\Message;

use Closure;
use Convenia\Pigeon\Contracts\Driver;
use PhpAmqpLib\Message\AMQPMessage;

interface Processor
{
    public function __construct(Driver $driver, Closure $callback, Closure $fallback = null);

    public function process(AMQPMessage $message);
}
