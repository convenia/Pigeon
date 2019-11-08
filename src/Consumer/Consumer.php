<?php

namespace Convenia\Pigeon\Consumer;

use Closure;
use Convenia\Pigeon\Drivers\DriverContract;
use Convenia\Pigeon\MessageProcessor\MessageProcessor;
use Illuminate\Foundation\Application;
use PhpAmqpLib\Message\AMQPMessage;

class Consumer implements ConsumerContract
{
    public $app;
    public $callback;
    public $fallback;

    protected $queue;
    protected $driver;
    protected $channel;

    public function __construct(Application $app, DriverContract $driver, string $queue)
    {
        $this->app = $app;
        $this->queue = $queue;
        $this->driver = $driver;
        $this->channel = $driver->getChannel();
    }

    public function consume(int $timeout = 0, bool $multiple = true)
    {
        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume(
            $this->queue,
            $consumer_tag = $this->app['config']['amqp.consumer.tag'],
            $no_local = false,
            $no_ack = false,
            $exclusive = false,
            $nowait = false,
            function (AMQPMessage $message) {
                $this->getCallback()->process($message);
            }
        );

        $this->wait($timeout, $multiple);
    }

    public function callback(Closure $callback): ConsumerContract
    {
        $this->callback = $callback;

        return $this;
    }

    public function fallback(Closure $fallback): ConsumerContract
    {
        $this->fallback = $fallback;

        return $this;
    }

    public function getCallback()
    {
        return new MessageProcessor($this->driver, $this->callback, $this->fallback);
    }

    private function wait(int $timeout, bool $multiple)
    {
        // Loop as long as the channel has callbacks registered
        do {
            $this->channel->wait(null, null, $timeout);
        } while (
            $this->channel->callbacks &&
            $multiple
        );
    }
}
