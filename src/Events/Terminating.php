<?php

namespace Convenia\Pigeon\Events;

use Illuminate\Foundation\Events\Dispatchable;
use PhpAmqpLib\Connection\AbstractConnection;

class Terminating
{
    use Dispatchable;

    public AbstractConnection $connection;

    public function __construct(AbstractConnection $connection) {
        $this->connection = $connection;
    }
}
