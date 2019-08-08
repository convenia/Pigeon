<?php

namespace Convenia\AMQP\Contracts;

use PhpAmqpLib\Message\AMQPMessage;

interface Publisher
{
    /**
     * Publish a message directly into queue.
     *
     * @param                                 $queue
     * @param \PhpAmqpLib\Message\AMQPMessage $message
     */
    public function direct($queue, AMQPMessage $message): void;

    /**
     * Publish message through a routing key.
     *
     * @param string      $routingKey
     * @param array       $message
     * @param string|null $queue
     * @param array       $properties
     */
    public function publish(string $routingKey, array $message, string $queue = null, array $properties = []): void;

    /**
     * Make a Remote Procedure Call asynchronously.
     *
     * @param string      $routingKey
     * @param array       $message
     * @param string|null $queue
     * @param array       $properties
     *
     * @return string response queue
     */
    public function asyncRpc(string $routingKey, array $message, string $queue = null, array $properties = []): string;

    /**
     * Make a Remote Procedure Call synchronously.
     *
     * @param string      $routingKey
     * @param array       $message
     * @param string|null $queue
     * @param array       $properties
     * @param int         $timeout
     *
     * @return mixed RPC response
     */
    public function rpc(string $routingKey, array $message, string $queue = null, array $properties = [], int $timeout = 10): array;
}
