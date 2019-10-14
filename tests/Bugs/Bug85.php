<?php

namespace Convenia\Pigeon\Tests\Bugs;

use Mockery;
use Convenia\Pigeon\Tests\TestCase;
use PhpAmqpLib\Channel\AMQPChannel;
use Convenia\Pigeon\Drivers\RabbitDriver;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class Bug85 extends TestCase
{
    protected $connection;

    protected $channel;

    protected $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = Mockery::mock(AMQPStreamConnection::class);
        $this->channel = Mockery::mock(AMQPChannel::class);
        $this->driver = Mockery::mock(RabbitDriver::class)->makePartial();

        $this->driver->shouldReceive('getConnection')
            ->andReturn($this->connection);
        $this->driver->app = $this->app;
    }

    public function test_it_should_publish_events_using_same_channel_id()
    {
        $this->channel->shouldReceive('exchange_declare')
            ->once();
        $this->channel->shouldReceive('basic_publish')
            ->once();
        $this->connection->shouldReceive('channel')
            ->with(null)
            ->once()
            ->andReturn($this->channel);

        $this->connection->shouldReceive('channel')
            ->once()
            ->with(2)
            ->andReturn($this->channel);

        $this->driver->emmit('anything', ['foo' => 'bar']);
    }
}