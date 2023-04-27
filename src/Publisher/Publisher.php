<?php

namespace Convenia\Pigeon\Publisher;

use Convenia\Pigeon\Drivers\DriverContract;
use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class Publisher implements PublisherContract
{
    protected $app;
    protected $driver;
    protected $exchange;
    protected $routing;
    protected $headers = [];

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

    public function publish(array $message, array $properties = [], int $channelId = null)
    {
        $msg = $this->makeMessage($message, $properties);
        $this->driver->getChannel($channelId)->basic_publish(
            $msg,
            $this->exchange,
            $this->routing
        );
    }

    private function makeMessage(array $data, array $properties = [])
    {
        return new AMQPMessage(
            json_encode($data),
            $this->getMessageProps($properties)
        );
    }

    private function getMessageProps(array $userProps): array
    {
        return array_merge([
            'content_type' => 'application/json',
            'content_encoding' => 'utf8',
            'correlation_id' => Str::uuid(),
            'expiration' => 60000000,
            'app_id' => $this->app['config']['app_name'],
            'application_headers' => new AMQPTable($this->getHeaders()),
        ], $userProps);
    }

    public function header(string $key, $value): PublisherContract
    {
        $this->headers = Arr::add($this->headers, $key, $value);

        return $this;
    }

    public function getHeaders(): array
    {
        $configHeaders = Arr::dot($this->app['config']->get('pigeon.headers'));
        $mapped = $this->mapToValues($configHeaders);

        return array_merge($mapped, $this->headers);
    }

    protected function mapToValues(array $headers)
    {
        $result = [];
        foreach ($headers as $key => $value) {
            $result[$key] = is_callable($value) ? call_user_func($value) : $value;
        }

        return $result;
    }
}
