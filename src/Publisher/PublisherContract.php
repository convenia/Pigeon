<?php

namespace Convenia\Pigeon\Publisher;

use Illuminate\Foundation\Application;
use Convenia\Pigeon\Drivers\DriverContract;

interface PublisherContract
{
    public function __construct(Application $app, DriverContract $driver, string $exchange);

    public function routing(string $key): self;

    public function bind(string $queue): self;

    public function header(string $key, $value): self;

    public function rpc(array $message, array $properties = []): string;
}
