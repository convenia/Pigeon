<?php

namespace Convenia\Pigeon\Publisher;

use Convenia\Pigeon\Drivers\DriverContract;
use Illuminate\Foundation\Application;

interface PublisherContract
{
    public function __construct(Application $app, DriverContract $driver, string $exchange);

    public function routing(string $key): self;

    public function bind(string $queue): self;

    public function publish(array $message, array $properties = []);

    public function header(string $key, $value): self;
}
