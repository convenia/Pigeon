<?php

namespace Convenia\Pigeon\Drivers;

use Closure;
use Convenia\Pigeon\Contracts\Consumer as ConsumerContract;
use Convenia\Pigeon\Contracts\Driver;
use Convenia\Pigeon\Contracts\Publisher as PublisherContract;
use Convenia\Pigeon\Exceptions\Events\EmptyEventException;
use Convenia\Pigeon\RabbitMQ\Consumer;
use Convenia\Pigeon\RabbitMQ\Publisher;
use Convenia\Pigeon\Support\Constants;
use Exception;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPHeartbeatMissedException;
use PhpAmqpLib\Wire\AMQPTable;

class RabbitMQDriver implements Driver
{
    public const EVENT_EXCHANGE = 'event';

    public const EVENT_EXCHANGE_TYPE = 'topic';

    /**
     * Laravel's application.
     *
     * @var Application $app
     */
    public $app;

    /**
     * The connection to RabbitMQ Service.
     *
     * @var AMQPStreamConnection $connection
     */
    protected AMQPStreamConnection $connection;

    /**
     * Class contructor.
     *
     * @return self
     */
    public function __construct(
        Application $app,
        AMQPStreamConnection $connection
    ) {
        $this->app = $app;

        $this->connection = $connection;

        if (extension_loaded('pcntl')) {
            $this->listenSignals();
        }

        $this->app->terminating(Closure::fromCallable([$this, 'quitHard']));
    }

    /**
     * Creates a consumer with a queue.
     *
     * @param  string  $name
     * @param  array  $properties
     *
     * @return ConsumerContract
     */
    public function queue(string $name, array $properties = []): ConsumerContract
    {
        $this->queueDeclare($name, $properties);

        return new Consumer($this->app, $this, $name);
    }

    /**
     * Created a new publisher defining a exchange.
     *
     * @param  string  $name
     * @param  string  $type
     *
     * @return PublisherContract
     *
     * @deprecated Use routing() function instead.
     */
    public function exchange(string $name, string $type = 'direct'): PublisherContract
    {
        ($name !== '') && $this->getChannel(2)->exchange_declare($name, $type, false, true, false, false, false, $this->getProps());

        return new Publisher($this->app, $this, $name);
    }

    /**
     * Creates a publisher for a routing key and using the app's configured exchange.
     *
     * @param  string  $name
     *
     * @return PublisherContract
     */
    public function routing(string $name = null): PublisherContract
    {
        $exchange = $this->app['config']['pigeon.exchange'];
        $type = $this->app['config']['pigeon.exchange_type'];

        $this->getChannel(2)->exchange_declare($exchange, $type, false, true, false, false, false, $this->getProps());

        return (new Publisher($this->app, $this, $exchange))->routing($name);
    }

    /**
     * Sends a message to the AMQP service.
     *
     * @param  string  $eventName
     * @param  array  $event
     * @param  array  $meta
     *
     * @return void
     */
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

    /**
     * Creates a consumer which will listen to the given queue.
     *
     * @param  string  $event
     *
     * @return ConsumerContract
     */
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

    /**
     * Gets the connection object of the class.
     * Tries to reconnect if the connectin it's lost.
     *
     * @return AMQPStreamConnection
     */
    public function connection(): AMQPStreamConnection
    {
        if (! $this->connection->isConnected() || $this->missedHeartBeat()) {
            $this->connection->reconnect();
        }

        return $this->connection;
    }

    public function getChannel(int $id = null): AMQPChannel
    {
        return $this->connection()->channel($id);
    }

    public function queueDeclare(string $name, array $properties)
    {
        try {
            $this->getChannel()->queue_declare($name, false, true, false, false, false, $this->getProps($properties));
        } catch (Exception $e) {
            Str::contains($e->getMessage(), 'PRECONDITION') ?: $this->handleQueuePrecondition($e, $name, $properties);
        }
    }

    protected function handleQueuePrecondition(Exception $e, string $name, array $properties)
    {
        switch ($this->app['config']['pigeon.queue_declare_exists']) {
            case Constants::IGNORE_PRECONDITION:
                return null;
            case Constants::REPLACE_ON_PRECONDITION:
                Log::critical('Handling declare precondition with: Constants::REPLACE_ON_PRECONDITION');
                $this->getChannel()->queue_delete($name);
                $this->queueDeclare($name, $properties);

                return null;
        }

        throw $e;
    }

    /**
     * Closes the connection disgracefully.
     *
     * @return void
     */
    public function quitHard(): void
    {
        $this->connection()->close();
    }

    /**
     * Closes the connection gracefully.
     *
     * @return void
     */
    public function quit(): void
    {
        $this->quitHard();
    }

    protected function missedHeartBeat(): bool
    {
        try {
            $this->connection->checkHeartBeat();
        } catch (AMQPHeartbeatMissedException $exception) {
            return true;
        }

        return false;
    }
}
