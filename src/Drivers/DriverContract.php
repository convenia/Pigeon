<?php

namespace Convenia\Pigeon\Drivers;

use Convenia\Pigeon\Consumer\ConsumerContract;
use Convenia\Pigeon\Publisher\PublisherContract;

interface DriverContract
{
    public function queue(string $name): ConsumerContract;

    public function exchange(string $name, string $type): PublisherContract;

    public function routing(string $name): PublisherContract;

    public function getConnection();

    public function getChannel();
}
