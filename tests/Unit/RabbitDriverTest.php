<?php

namespace Convenia\Pigeon\Tests\Unit;

use Mockery;
use Convenia\Pigeon\Tests\TestCase;
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

    private $queue = 'some.queue';

    protected function setUp()
    {
        parent::setUp();

        $this->connection = Mockery::mock(AbstractConnection::class);
        $this->channel = Mockery::mock(AMQPChannel::class);
        $this->driver = Mockery::mock('Convenia\Pigeon\Drivers\RabbitDriver[makeConnection]', [$this->app]);

        $this->driver->shouldReceive('makeConnection')
            ->once()
            ->andReturn($this->connection);
        $this->connection->shouldReceive('channel')
            ->with(1)
            ->andReturn($this->channel);
        $this->connection->shouldReceive('isConnected')
            ->andReturn(false, true);
        $this->connection->shouldReceive('reconnect')
            ->once();
    }

    public function test_it_should_return_connection()
    {
        $this->assertEquals($this->connection, $this->driver->getConnection());
    }

    public function test_it_should_return_channel()
    {
        $this->assertEquals($this->channel, $this->driver->getchannel());
    }
}
