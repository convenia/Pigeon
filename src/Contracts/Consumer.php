<?php

namespace Convenia\AMQP\Contracts;

interface Consumer
{
    public function consume(string $queue, int $timeout = 1, bool $multiple = true);

    public function setCallback(\Closure $callback);

    public function setFallback(\Closure $fallback);
}
