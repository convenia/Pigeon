<?php

namespace Convenia\Pigeon\Contracts;

use Closure;
use Convenia\Pigeon\Contracts\Driver;
use Illuminate\Foundation\Application;

interface Consumer
{
    public function __construct(Application $app, Driver $driver, string $queue);

    public function consume(int $timeout = 5, bool $multiple = true);

    public function callback(Closure $callback): self;

    public function fallback(Closure $fallback): self;
}
