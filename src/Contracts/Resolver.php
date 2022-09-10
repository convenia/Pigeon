<?php

namespace Convenia\Pigeon\Contracts;

interface Resolver
{
    public function ack();

    public function reject(bool $requeue = true);

    public function headers(string $key = null);
}
