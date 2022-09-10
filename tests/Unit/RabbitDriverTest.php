<?php

namespace Convenia\Pigeon\Tests\Unit;

use Convenia\Pigeon\Tests\Support\ConnectsToRabbitMQ;
use Convenia\Pigeon\Tests\TestCase;
use Mockery\MockInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPHeartbeatMissedException;

class RabbitMQDriverTest extends TestCase
{
    use ConnectsToRabbitMQ;

    public function test_it_should_return_connection_if_it_is_connected()
    {
        $pigeon = $this->app->make('pigeon');

        $this->assertInstanceOf(AMQPStreamConnection::class, $pigeon->driver()->connection());
    }

    public function test_it_should_reconnect_and_return_connection_if_heartbeat_is_missed()
    {
        $this->partialMock(AMQPStreamConnection::class, function (MockInterface $mock) {
            $mock->shouldReceive('isConnected')->once()->andReturn(true);
            $mock->shouldReceive('checkHeartBeat')->andThrow(new AMQPHeartbeatMissedException());
            $mock->shouldReceive('reconnect')->once();
        });

        $pigeon = $this->app->make('pigeon');

        $this->assertInstanceOf(AMQPStreamConnection::class, $pigeon->driver()->connection());
    }

    public function test_it_should_reconnect_and_return_connection_if_not_connected()
    {
        $this->partialMock(AMQPStreamConnection::class, function (MockInterface $mock) {
            $mock->shouldReceive('isConnected')->once()->andReturn(false);
            $mock->shouldReceive('reconnect')->once();
        });

        $pigeon = $this->app->make('pigeon');

        $this->assertInstanceOf(AMQPStreamConnection::class, $pigeon->driver()->connection());
    }

    public function test_it_should_return_channel()
    {
        $mockChannel = $this->partialMock(AMQPChannel::class);

        $this->partialMock(AMQPStreamConnection::class, function (MockInterface $mock) use ($mockChannel) {
            $mock->shouldReceive('isConnected')->once()->andReturn(true);
            $mock->shouldReceive('checkHeartBeat')->once();
            $mock->shouldReceive('channel')->once()->andReturn($mockChannel);
        });

        $pigeon = $this->app->make('pigeon');

        $this->assertEquals($mockChannel, $pigeon->driver()->getchannel());
    }
}
