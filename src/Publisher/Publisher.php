<?php

namespace Convenia\Pigeon\Publisher;

use Convenia\Pigeon\Drivers\DriverContract;
use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;
use PhpAmqpLib\Message\AMQPMessage;
use Webpatser\Uuid\Uuid;

class Publisher implements PublisherContract
{
    protected $app;
    protected $driver;
    protected $exchange;
    protected $routing;

    public function __construct(Application $app, DriverContract $driver, string $exchange)
    {
        $this->app = $app;
        $this->driver = $driver;
        $this->exchange = $exchange;
    }

    public function routing(string $key): PublisherContract
    {
        $this->routing = $key;

        return $this;
    }

    public function bind(string $queue): PublisherContract
    {
        $this->driver->getChannel()->queue_bind($queue, $this->exchange, $this->routing);

        return $this;
    }

    public function publish(array $message, array $properties = [])
    {
        $msg = $this->makeMessage($message, $properties);
        $this->driver->getChannel()->basic_publish(
            $msg,
            $this->exchange,
            $this->routing
        );
    }

    public function rpc(array $message, array $properties = []): string
    {
        [$response_via,] = $this->driver->getChannel()->queue_declare();
        Arr::add($properties, 'reply_to', $response_via);
        $msg = $this->makeMessage($message, $properties);
        $this->driver->getChannel()->basic_publish(
            $msg,
            $this->exchange,
            $this->routing
        );

        return $response_via;
    }

    private function makeMessage(array $data, array $properties)
    {
        return new AMQPMessage(
            json_encode($data),
            $this->getMessageProps($properties)
        );
    }

    private function getMessageProps(array $userProps): array
    {
        return array_merge([
            'content_type'     => 'application/json',
            'content_encoding' => 'utf8',
            'correlation_id'   => Uuid::generate()->string,
            'expiration'       => 60000000,
            'app_id'           => $this->app['config']['app_name'],
        ], $userProps);
    }
}
