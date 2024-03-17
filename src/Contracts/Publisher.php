<?php

namespace Convenia\Pigeon\Contracts;

use Convenia\Pigeon\Contracts\Driver;
use Illuminate\Foundation\Application;

interface Publisher
{
    public function __construct(Application $app, Driver $driver, string $exchange);

    public function routing(string $key): self;

    public function bind(string $queue): self;

    public function publish(array $message, array $properties = []);

    public function header(string $key, $value): self;
}
