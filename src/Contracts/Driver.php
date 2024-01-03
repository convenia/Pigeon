<?php

namespace Convenia\Pigeon\Contracts;

use Convenia\Pigeon\Contracts\Consumer;
use Convenia\Pigeon\Contracts\Publisher;
use PhpAmqpLib\Connection\AMQPStreamConnection;

interface Driver
{
    /**
     * Creates a consumer with a queue.
     *
     * @param  string  $name
     * @param  array  $properties
     *
     * @return Consumer
     */
    public function queue(string $name, array $properties = []): Consumer;

    /**
     * Created a new publisher defining a exchange.
     *
     * @param  string  $name
     * @param  string  $type
     *
     * @return Publisher
     *
     * @deprecated Use routing() function instead.
     */
    public function exchange(string $name, string $type = 'direct'): Publisher;

    /**
     * Creates a consumer which will listen to the given queue.
     *
     * @param  string  $event
     *
     * @return Consumer
     */
    public function events(string $event = '#'): Consumer;

    /**
     * Sends a message to the AMQP service.
     *
     * @param  string  $eventName
     * @param  array  $event
     * @param  array  $meta
     *
     * @return void
     */
    public function dispatch(string $eventName, array $event, array $meta = []): void;

    /**
     * Creates a publisher for a routing key and using the app's configured exchange.
     *
     * @param  string  $name
     *
     * @return Publisher
     */
    public function routing(string $name = null): Publisher;

    /**
     * Gets the connection object of the class.
     * Tries to reconnect if the connectin it's lost.
     *
     * @return AMQPStreamConnection
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
