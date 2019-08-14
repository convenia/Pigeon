<?php

namespace Convenia\Pigeon\Consumer;

use Closure;
use Convenia\Pigeon\Drivers\DriverContract;
use Illuminate\Foundation\Application;

interface ConsumerContract
{
    public function __construct(Application $app, DriverContract $driver, string $queue);

    public function consume(int $timeout = 5, bool $multiple = true);

    public function callback(Closure $callback): self;

    public function fallback(Closure $fallback): self;
}
