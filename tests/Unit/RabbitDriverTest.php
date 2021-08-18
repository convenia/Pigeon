<?php

namespace Convenia\Pigeon\Tests\Unit;

use Convenia\Pigeon\Tests\TestCase;
use Mockery;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;

class RabbitDriverTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject | \Convenia\Pigeon\Drivers\Driver
     */
    private $driver;
    private $connection;
    private $channel;

    public function mocksSetUp(): void
    {
        $this->connection = Mockery::mock(AbstractConnection::class);
        $this->channel = Mockery::mock(AMQPChannel::class);
        $this->driver = Mockery::mock('Convenia\Pigeon\Drivers\RabbitDriver[makeConnection]', [$this->app]);

        $this->driver->shouldReceive('makeConnection')
            ->once()
            ->andReturn($this->connection);
        $this->connection->shouldReceive('channel')
            ->andReturn($this->channel);
        $this->connection->shouldReceive('isConnected')
            ->andReturn(false, true);
        $this->connection->shouldReceive('reconnect')
            ->once();
    }

    public function test_it_should_return_connection()
    {
        $this->mocksSetUp();
        $this->assertEquals($this->connection, $this->driver->getConnection());
    }

    public function test_it_should_return_channel()
    {
        $this->mocksSetUp();
        $this->assertEquals($this->channel, $this->driver->getchannel());
    }

    public function test_it_should_return_not_missed_heart_beat()
    {
        $this->app['pigeon']->driver('rabbit')->getConnection();
        $this->assertFalse($this->app['pigeon']->driver('rabbit')->missedHeartBeat());
    }
}
