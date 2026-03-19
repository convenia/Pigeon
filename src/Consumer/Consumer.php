<?php

namespace Convenia\Pigeon\Consumer;

use Closure;
use Convenia\Pigeon\Drivers\DriverContract;
use Convenia\Pigeon\MessageProcessor\MessageProcessor;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;
use Throwable;

class Consumer implements ConsumerContract
{
    public $app;
    public $callback;
    public $fallback;
    public $multiple;
    public $timeout;

    protected $queue;
    protected $driver;
    protected $channel;

    public function __construct(Application $app, DriverContract $driver, string $queue)
    {
        $this->app = $app;
        $this->queue = $queue;
        $this->driver = $driver;
        $this->channel = $driver->getChannel();
        $this->multiple = true;
        $this->timeout = 0;
    }

    public function consume(int $timeout = 0, bool $multiple = true): void
    {
        $this->setupChannel();

        $this->timeout = $timeout;
        $this->multiple = $multiple;
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

    public function getCallback(): MessageProcessor
    {
        return new MessageProcessor($this->driver, $this->callback, $this->fallback);
    }

    private function setupChannel(): void
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
    }

    private function shouldLog(): bool
    {
        return (bool) ($this->app['config']['pigeon.consumer.enable_consumer_logs'] ?? true);
    }

    private function reconnect(): void
    {
        $maxAttempts = (int) ($this->app['config']['pigeon.consumer.reconnect_attempts'] ?? 3);
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $attempt++;

            try {
                sleep($attempt);

                $this->channel = $this->driver->getChannel();
                $this->setupChannel();

                Log::when($this->shouldLog())->info('Pigeon: reconnected successfully.', [
                    'queue' => $this->queue,
                    'attempt' => $attempt,
                ]);

                return;
            } catch (Throwable $e) {
                Log::when($this->shouldLog())->warning('Pigeon: reconnection attempt failed.', [
                    'queue' => $this->queue,
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts,
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        throw new RuntimeException(
            "Pigeon: failed to reconnect after {$maxAttempts} attempts for queue [{$this->queue}]."
        );
    }

    private function wait(int $timeout, bool $multiple): void
    {
        do {
            try {
                $this->channel->wait(null, null, $timeout);
            } catch (AMQPConnectionClosedException $e) {
                Log::when($this->shouldLog())->warning('Pigeon: connection lost, attempting to reconnect...', [
                    'queue' => $this->queue,
                    'exception' => $e->getMessage(),
                ]);

                $this->reconnect();
            }
        } while (
            $this->channel->callbacks &&
            $multiple
        );
    }
}
