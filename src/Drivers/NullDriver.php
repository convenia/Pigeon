<?php

namespace Convenia\AMQP\Drivers;

use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class NullDriver.
 */
class NullDriver extends Driver
{
    /**
     * @param string $queue
     * @param int    $timeout
     * @param bool   $multiple
     */
    public function consume(string $queue, int $timeout = 1, bool $multiple = true): void
    {
    }

    /**
     * @param string $queue
     * @param array  $props
     *
     * @return string
     */
    public function declareQueue(string $queue, array $props = []): string
    {
        return null;
    }

    /**
     * @param string $exchange
     * @param string $type
     * @param array  $props
     *
     * @return bool
     */
    public function declareExchange(string $exchange, string $type = 'direct', array $props): bool
    {
        return false;
    }

    /**
     * @param string $exchange
     * @param string $queue
     * @param string $routingKey
     */
    public function bindQueue(string $exchange, string $queue, string $routingKey): void
    {
    }

    protected function setup(): void
    {
    }

    /**
     * Publish a message directly into queue.
     *
     * @param                                 $queue
     * @param \PhpAmqpLib\Message\AMQPMessage $message
     */
    public function direct($queue, AMQPMessage $message): void
    {
    }

    /**
     * Publish message through a routing key.
     *
     * @param string      $routingKey
     * @param array       $message
     * @param string|null $queue
     * @param array       $properties
     */
    public function publish(string $routingKey, array $message, string $queue = null, array $properties = []): void
    {
    }

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
    public function asyncRpc(string $routingKey, array $message, string $queue = null, array $properties = []): string
    {
        return null;
    }

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
    public function rpc(
        string $routingKey,
        array $message,
        string $queue = null,
        array $properties = [],
        int $timeout = 10
    ): array {
        return [];
    }
}
