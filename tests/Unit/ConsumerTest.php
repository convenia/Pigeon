<?php

namespace Convenia\Pigeon\Tests\Unit;

use Convenia\Pigeon\Consumer\Consumer;
use Convenia\Pigeon\Drivers\RabbitDriver;
use Convenia\Pigeon\MessageProcessor\MessageProcessor;
use Convenia\Pigeon\Tests\TestCase;
use Mockery;
use PhpAmqpLib\Channel\AMQPChannel;

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
    }

    public function test_it_should_bind_in_an_exchange_with_routing_keys_and_declare_the_exchange()
    {
        $this->driver->shouldReceive('getChannel')
            ->times(3)
            ->andReturn($this->channel);

        // setup
        $exchange = 'my.awesome.exchange';
        $type = 'my.awesome.exchange.type';
        $routing = 'my.awesome.service';
        $queue = 'my.awesome.queue';

        // assert
        $this->channel->shouldReceive('queue_bind')->with(
            $queue,
            $exchange,
            $routing
        )->once();

        $this->driver->shouldReceive('getProps')
            ->once()
            ->andReturn([]);

        $this->channel->shouldReceive('exchange_declare')
            ->with($exchange, $type, false, true, false, false, false, []);

        // act
        $consumer = new Consumer($this->app, $this->driver, $queue);
        $consumer->exchange($exchange, $type)->routing($routing)->bind($queue);
    }

    public function test_it_should_consume_queue_without_multiple_loops()
    {
        $queue = 'some.queue';

        // setup
        $this->driver->shouldReceive('getChannel')
            ->once()
            ->andReturn($this->channel);

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
        $this->driver->shouldReceive('getChannel')
            ->once()
            ->andReturn($this->channel);

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

        $this->assertTrue($times == 2, 'Called two times');
    }

    public function test_it_should_return_a_message_processor()
    {
        //setup
        $this->driver->shouldReceive('getChannel')
            ->once()
            ->andReturn($this->channel);

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
}
