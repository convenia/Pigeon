<?php

namespace Convenia\Pigeon\Contracts;

use Convenia\Pigeon\Contracts\Consumer;
use Convenia\Pigeon\Contracts\Publisher;

interface Driver
{
    public function queue(string $name): Consumer;

    public function exchange(string $name, string $type): Publisher;

    /**
     * Creates a "consumer" which will listen the desired queue.
     *
     * @param  string  $event
     * @return \Convenia\Pigeon\Contracts\Consumer
     */
    public function events(string $event = '*'): Consumer;

    /**
     * Send message to the AMQP Service in the default exchange.
     *
     * @param  string  $eventName
     * @param  array  $event
     * @param  array  $meta
     * @return void
     */
    public function dispatch(string $eventName, array $event, array $meta = []): void;

    public function routing(string $name): Publisher;

    /**
     * Gets the connection object of the class.
     * Tries to reconnect if the connectin it's lost.
     *
     * @return \PhpAmqpLib\Connection\AMQPStreamConnection
     */
    public function connection();

    public function getChannel(int $id = null);

    /**
     * Closes the connection disgracefully.
     *
     * @return void
     */
    public function quitHard(): void;

    /**
     * Closes the connection gracefully.
     *
     * @return void
     */
    public function quit(): void;

    public function queueDeclare(string $name, array $properties);
}
