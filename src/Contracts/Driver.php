<?php

namespace Convenia\Pigeon\Contracts;

use Convenia\Pigeon\Contracts\Consumer;
use Convenia\Pigeon\Contracts\Publisher;

interface Driver
{
    public function queue(string $name): Consumer;

    public function exchange(string $name, string $type): Publisher;

    public function events(string $event = '*'): Consumer;

    public function dispatch(string $eventName, array $event, array $meta = []): void;

    public function routing(string $name): Publisher;

    public function getConnection();

    public function getChannel(int $id = null);
}
