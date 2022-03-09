<?php

namespace Convenia\Pigeon\Drivers;

use Convenia\Pigeon\Consumer\Consumer;
use Convenia\Pigeon\Consumer\ConsumerContract;
use Convenia\Pigeon\Exceptions\Events\EmptyEventException;
use Convenia\Pigeon\Publisher\Publisher;
use Convenia\Pigeon\Publisher\PublisherContract;
use Illuminate\Foundation\Application;
use PhpAmqpLib\Wire\AMQPTable;

abstract class Driver implements DriverContract
{
    public const EVENT_EXCHANGE = 'event';

    public const EVENT_EXCHANGE_TYPE = 'topic';

    public $app;

    protected $connection;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->setup();
    }

    public function setup()
    {
        if (extension_loaded('pcntl')) {
            $this->listenSignals();
        }

        $this->app->terminating(\Closure::fromCallable([$this, 'quitHard']));
    }

    public function queue(string $name, array $properties = []): ConsumerContract
    {
        $this->queueDeclare($name, $properties);

        return new Consumer($this->app, $this, $name);
    }

    public function exchange(string $name, string $type = 'direct'): PublisherContract
    {
        ($name !== '') && $this->getChannel(2)->exchange_declare($name, $type, false, true, false, false, false, $this->getProps());

        return new Publisher($this->app, $this, $name);
    }

    public function routing(string $name = null): PublisherContract
    {
        $exchange = $this->app['config']['pigeon.exchange'];
        $type = $this->app['config']['pigeon.exchange_type'];

        $this->getChannel(2)->exchange_declare($exchange, $type, false, true, false, false, false, $this->getProps());

        return (new Publisher($this->app, $this, $exchange))->routing($name);
    }

    public function dispatch(string $eventName, array $event, array $meta = []): void
    {
        throw_if(empty($event), new EmptyEventException());
        $publisher = $this->exchange(self::EVENT_EXCHANGE, self::EVENT_EXCHANGE_TYPE)->routing($eventName);

        $publisher->header('category', $eventName);
        foreach ($meta as $key => $value) {
            $publisher->header($key, $value);
        }

        $publisher->publish($event, [], 3);
    }

    public function events(string $event = '#'): ConsumerContract
    {
        $app_name = str_replace(' ', '.', $this->app['config']['pigeon.app_name']);
        $app_name = strtolower($app_name);
        $queue = "{$event}.{$app_name}";
        $consumer = $this->queue($queue);
        $this->exchange(self::EVENT_EXCHANGE, self::EVENT_EXCHANGE_TYPE)
            ->routing($event)
            ->bind($queue);

        return $consumer;
    }

    public function getProps(array $userProps = [])
    {
        $deadExchange = $this->app['config']['pigeon.dead.exchange'];
        $deadRouting = $this->app['config']['pigeon.dead.routing_key'];

        $dead = [];

        if ($deadExchange) {
            $dead['x-dead-letter-exchange'] = $deadExchange;
        }
        if ($deadExchange && $deadRouting) {
            $dead['x-dead-letter-routing-key'] = $deadRouting;
        }

        return new AMQPTable(array_merge($dead, $userProps));
    }

    protected function listenSignals(): void
    {
        defined('AMQP_WITHOUT_SIGNALS') ?: define('AMQP_WITHOUT_SIGNALS', false);

        pcntl_async_signals(true);

        pcntl_signal(SIGTERM, [$this, 'signalHandler']);
        pcntl_signal(SIGINT, [$this, 'signalHandler']);
        pcntl_signal(SIGQUIT, [$this, 'signalHandler']);
    }

    public function signalHandler($signalNumber)
    {
        switch ($signalNumber) {
            case SIGTERM:  // 15 : supervisor default stop
                $this->quitHard();
                break;
            case SIGQUIT:  // 3  : kill -s QUIT
                $this->quitHard();
                break;
            case SIGINT:   // 2  : ctrl+c
                $this->quit();
                break;
        }
    }

    abstract public function quitHard();

    abstract public function quit();

    abstract public function getConnection();

    abstract public function queueDeclare(string $name, array $properties);
}
