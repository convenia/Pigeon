<?php

namespace Convenia\AMQP\Contracts;

interface Resolver
{
    public function reject(bool $requeue = true): void;

    public function ack(): void;
}
