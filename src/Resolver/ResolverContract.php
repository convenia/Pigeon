<?php

namespace Convenia\Pigeon\Resolver;

interface ResolverContract
{
    public function ack();

    public function reject(bool $requeue = true);

    public function response(array $data);
}
