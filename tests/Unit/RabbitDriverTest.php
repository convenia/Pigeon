<?php

namespace Convenia\Pigeon\Tests\Unit;

use Convenia\Pigeon\Tests\TestCase;
use Mockery;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Exception\AMQPHeartbeatMissedException;

class RabbitDriverTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject | \Convenia\Pigeon\Drivers\Driver
     */
    private $driver;
    private $connection;
    private $channel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = Mockery::mock(AbstractConnection::class);
        $this->channel = Mockery::mock(AMQPChannel::class);
        $this->driver = Mockery::mock(
            'Convenia\Pigeon\Drivers\RabbitDriver[makeConnection]', [$this->app]
        );

        $this->driver->shouldReceive('makeConnection')
            ->once()
            ->andReturn($this->connection);
    }

    public function test_it_should_return_connection_if_it_is_connected()
    {
        $this->connection->shouldReceive('isConnected')->once()->andReturn(true);
        $this->connection->shouldReceive('checkHeartBeat')->once()->andReturn(true);

        $this->assertEquals($this->connection, $this->driver->getConnection());
    }

    public function test_it_should_reconnect_and_return_connection_if_heartbeat_is_missed()
    {
        $this->connection->shouldReceive('isConnected')->once()->andReturn(true);
        $this->connection->shouldReceive('reconnect')->once();
        $this->connection->shouldReceive('checkHeartBeat')
            ->andThrow(new AMQPHeartbeatMissedException());

        $this->assertEquals($this->connection, $this->driver->getConnection());
    }

    public function test_it_should_reconnect_and_return_connection_if_not_connected()
    {
        $this->connection->shouldReceive('isConnected')->once()->andReturn(false);
        $this->connection->shouldReceive('reconnect')->once();

        $this->assertEquals($this->connection, $this->driver->getConnection());
    }

    public function test_it_should_return_channel()
    {
        $this->connection->shouldReceive('isConnected')->once()->andReturn(true);
        $this->connection->shouldReceive('checkHeartBeat')->once();
        $this->connection->shouldReceive('channel')->once()->andReturn($this->channel);

        $this->assertEquals($this->channel, $this->driver->getchannel());
    }
}
