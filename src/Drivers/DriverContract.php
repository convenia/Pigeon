<?php

namespace Convenia\Pigeon\Drivers;

use Convenia\Pigeon\Consumer\ConsumerContract;
use Convenia\Pigeon\Publisher\PublisherContract;

interface DriverContract
{
    public function queue(string $name): ConsumerContract;

    public function exchange(string $name, string $type): PublisherContract;

    public function events(string $event = '*'): ConsumerContract;

    public function emmit(string $eventName, array $event, array $meta = []): void;

    public function routing(string $name): PublisherContract;

    public function getConnection();

    public function getChannel(int $id = null);
}
