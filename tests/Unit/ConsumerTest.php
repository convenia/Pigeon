<?php

namespace Convenia\Pigeon\Tests\Unit;

use Convenia\Pigeon\Consumer\Consumer;
use Convenia\Pigeon\Drivers\RabbitDriver;
use Convenia\Pigeon\MessageProcessor\MessageProcessor;
use Convenia\Pigeon\Tests\TestCase;
use Mockery;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;

class ConsumerTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject | \Convenia\Pigeon\Drivers\Driver
     */
    private $driver;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\PhpAmqpLib\Channel\AMQPChannel
     */
    private $channel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->channel = Mockery::mock(AMQPChannel::class);
        $this->driver = Mockery::mock(RabbitDriver::class);

        $this->driver->shouldReceive('getChannel')
            ->once()
            ->andReturn($this->channel);
    }

    public function test_it_should_consume_queue_without_multiple_loops()
    {
        $queue = 'some.queue';

        // setup
        $this->channel->shouldReceive('basic_qos')->once();
        $this->channel->shouldReceive('wait')->once()->with(null, null, 5);
        $this->channel->callbacks = [
            'first callback',
            'second callback',
        ];
        $this->channel->shouldReceive('basic_consume')
            ->once()
            ->with($queue, $this->app['config']['pigeon.consumer.tag'], false, false, false, false, Mockery::type('closure'));
        $consumer = new Consumer($this->app, $this->driver, $queue);

        $consumer->consume(5, false);
    }

    public function test_it_should_consume_queue_with_multiple_loops()
    {
        $queue = 'some.queue';
        $multiple = true;
        $times = 0;
        $this->channel->callbacks = [
            'first callback',
            'second callback',
        ];

        // setup
        $this->channel->shouldReceive('basic_qos')->once();
        $this->channel->shouldReceive('wait')
            ->twice()
            ->with(null, null, 5)
            ->andReturnUsing(function () use (&$times) {
                // remove a callback from array to control the infinity loop
                $index = count($this->channel->callbacks) - 1;
                unset($this->channel->callbacks[$index]);
                // check times the function is called
                $times++;
            });
        $this->channel->shouldReceive('basic_consume')
            ->once()
            ->with($queue, $this->app['config']['pigeon.consumer.tag'], false, false, false, false, Mockery::type('closure'));
        $consumer = new Consumer($this->app, $this->driver, $queue);

        $consumer->consume(5, true);

        $this->assertTrue($times === 2, 'Called two times');
    }

    public function test_it_should_return_a_message_processor()
    {
        //setup
        $queue = 'some.queue';
        $consumer = new Consumer($this->app, $this->driver, $queue);
        $consumer->callback(function () {
        });
        $consumer->fallback(function () {
        });

        // act
        $processor = $consumer->getCallback();

        $this->assertInstanceOf(MessageProcessor::class, $processor);
    }

    public function test_it_should_use_consumer_tag_from_config()
    {
        $queue = 'some.queue';
        $customTag = 'my-custom-consumer-tag';

        $this->app['config']->set('pigeon.consumer.tag', $customTag);

        $this->channel->shouldReceive('basic_qos')->once();
        $this->channel->shouldReceive('basic_consume')
            ->once()
            ->with($queue, $customTag, false, false, false, false, Mockery::type('closure'));
        $this->channel->shouldReceive('wait')->once()->with(null, null, 5);
        $this->channel->callbacks = ['callback'];

        $consumer = new Consumer($this->app, $this->driver, $queue);
        $consumer->consume(5, false);
    }

    public function test_it_should_reconnect_when_connection_is_lost()
    {
        // Satisfy setUp's getChannel()->once() expectation
        new Consumer($this->app, $this->driver, 'setup.queue');

        $this->app['config']->set('pigeon.consumer.reconnect_attempts', 1);

        $queue = 'some.queue';
        $oldChannel = Mockery::mock(AMQPChannel::class);
        $newChannel = Mockery::mock(AMQPChannel::class);
        $driver = Mockery::mock(RabbitDriver::class);

        $driver->shouldReceive('getChannel')
            ->twice()
            ->andReturn($oldChannel, $newChannel);

        // Old channel: setup + wait throws connection closed
        $oldChannel->shouldReceive('basic_qos')->once();
        $oldChannel->shouldReceive('basic_consume')
            ->once()
            ->with($queue, $this->app['config']['pigeon.consumer.tag'], false, false, false, false, Mockery::type('closure'));
        $oldChannel->shouldReceive('wait')
            ->once()
            ->with(null, null, 5)
            ->andThrow(new AMQPConnectionClosedException('CONNECTION_FORCED'));

        // New channel after reconnect: setup + wait succeeds
        $newChannel->callbacks = ['callback'];
        $newChannel->shouldReceive('basic_qos')->once();
        $newChannel->shouldReceive('basic_consume')
            ->once()
            ->with($queue, $this->app['config']['pigeon.consumer.tag'], false, false, false, false, Mockery::type('closure'));
        $newChannel->shouldReceive('wait')
            ->once()
            ->with(null, null, 5)
            ->andReturnUsing(function () use ($newChannel) {
                $newChannel->callbacks = [];
            });

        $consumer = new Consumer($this->app, $driver, $queue);
        $consumer->consume(5, true);
    }

    public function test_it_should_throw_when_max_reconnect_attempts_exceeded()
    {
        // Satisfy setUp's getChannel()->once() expectation
        new Consumer($this->app, $this->driver, 'setup.queue');

        $this->app['config']->set('pigeon.consumer.reconnect_attempts', 1);

        $queue = 'some.queue';
        $oldChannel = Mockery::mock(AMQPChannel::class);
        $driver = Mockery::mock(RabbitDriver::class);

        $callCount = 0;
        $driver->shouldReceive('getChannel')
            ->andReturnUsing(function () use (&$callCount, $oldChannel) {
                $callCount++;
                if ($callCount === 1) {
                    return $oldChannel;
                }
                throw new \RuntimeException('Connection refused');
            });

        $oldChannel->shouldReceive('basic_qos')->once();
        $oldChannel->shouldReceive('basic_consume')
            ->once()
            ->with($queue, $this->app['config']['pigeon.consumer.tag'], false, false, false, false, Mockery::type('closure'));
        $oldChannel->shouldReceive('wait')
            ->once()
            ->andThrow(new AMQPConnectionClosedException('CONNECTION_FORCED'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Pigeon: failed to reconnect after 1 attempts for queue [{$queue}].");

        $consumer = new Consumer($this->app, $driver, $queue);
        $consumer->consume(5, true);
    }
}
