<?php

namespace Convenia\AMQP\Drivers;

use Exception;
use Convenia\AMQP\Contracts\Publisher;
use Convenia\AMQP\Contracts\Consumer;
use Illuminate\Foundation\Application;
use Webpatser\Uuid\Uuid;

/**
 * Class Driver.
 */
abstract class Driver implements Publisher, Consumer
{
    /**
     * @var \Closure
     */
    protected $callback;

    /**
     * @var \Closure
     */
    protected $fallback;

    /**
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Driver constructor.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->setup();
    }

    /**
     * Declare a queue.
     *
     * @param string $queue
     * @param array  $props
     *
     * @return string
     */
    abstract public function declareQueue(string $queue, array $props = []): string;

    /**
     * Declare a exchange.
     *
     * @param string $exchange
     * @param string $type
     * @param array  $props
     *
     * @return bool
     */
    abstract public function declareExchange(string $exchange, string $type = 'direct', array $props): bool;

    /**
     * Bind a queue to exchange using or not a routing key.
     *
     * @param string      $exchange
     * @param string      $queue
     * @param string|null $routingKey
     */
    abstract public function bindQueue(string $exchange, string $queue, string $routingKey);

    /**
     * Setup connection for use.
     */
    abstract protected function setup();

    /**
     * @param \Closure $callback
     */
    public function setCallback(\Closure $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @param \Closure $fallback
     */
    public function setFallback(\Closure $fallback)
    {
        $this->fallback = $fallback;
    }

    /**
     * @return string
     */
    protected function applicationTag(): string
    {
        return $this->app['config']['amqp.consumer.tag'] ?: null;
    }

    /**
     * @return string
     */
    protected function correlationId(): string
    {
        try {
            return Uuid::generate(4, $this->applicationTag());
        } catch (Exception $e) {
            return uniqid('', true);
        }
    }
}
