<?php

namespace Convenia\Pigeon\Tests\Unit;

use Mockery;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Convenia\Pigeon\Drivers\Driver;
use Convenia\Pigeon\Tests\TestCase;
use PhpAmqpLib\Channel\AMQPChannel;
use Convenia\Pigeon\Consumer\ConsumerContract;
use Convenia\Pigeon\Publisher\PublisherContract;

class DriverTest extends TestCase
{
    private $driver;
    private $channel;

    private $queue = 'some.queue';

    protected function setUp()
    {
        parent::setUp();
        $this->channel = Mockery::mock(AMQPChannel::class);
        $this->driver = $this->getMockForAbstractClass(Driver::class, [$this->app]);
        $this->driver->method('getChannel')->willReturn($this->channel);
    }

    public function test_it_should_declare_a_queue()
    {
        // setup and asserts
        $this->channel->shouldReceive('queue_declare')
            ->with($this->queue, $passive = false, $durable = true, false, $delete = false, false, ['some' => 'prop'])
            ->once();

        // act
        $consumer = $this->driver->queue($this->queue, ['some' => 'prop']);

        // assert
        $this->assertInstanceOf(ConsumerContract::class, $consumer);
    }

    public function test_it_should_declare_exchange()
    {
        $exchange = 'my.exchange';
        $type = 'fanout';

        // setup and asserts
        $this->channel->shouldReceive('exchange_declare')
            ->with($exchange, $type, false, true, false, false, false, Mockery::type(AMQPTable::class));

        // act
        $publisher = $this->driver->exchange($exchange, $type);

        // assert
        $this->assertInstanceOf(PublisherContract::class, $publisher);
    }

    public function test_it_should_declare_exchange_bind_key()
    {
        $exchange = 'my.exchange';
        $routing = 'exchange.queue';
        $type = 'fanout';
        $queue = 'my.queue';

        // setup and asserts
        $this->channel->shouldReceive('exchange_declare')
            ->with($exchange, $type, true, true, false, false, false, Mockery::type(AMQPTable::class));
        $this->channel->shouldReceive('queue_bind')->with($queue, $exchange, $routing)->once();

        $this->app['config']->set('pigeon.exchange', $exchange);
        $this->app['config']->set('pigeon.exchange_type', $type);

        // act
        $publisher = $this->driver->routing($routing)
            ->bind($queue);

        // assert
        $this->assertInstanceOf(PublisherContract::class, $publisher);
    }

    public function test_it_should_publish_event()
    {
        // setup
        $event_name = str_random(8);
        $event_content = [
            'foo' => 'fighters',
        ];

        // assert
        $this->channel->shouldReceive('basic_publish')->with(
            Mockery::type(AMQPMessage::class),
            Driver::EVENT_EXCHANGE,
            $event_name
        )->once();
        $this->channel->shouldReceive('exchange_declare')->with(
            Driver::EVENT_EXCHANGE,
            'direct',
            false,
            true,
            false,
            false,
            false,
            Mockery::type(AMQPTable::class)
        )->once();

        // act
        $this->driver->emmit($event_name, $event_content);
    }

    public function test_it_should_not_publish_empty_event()
    {
        // setup
        $event_name = 'my.event.name';
        $event_content = [];

        // assert
        $this->expectExceptionMessage('Cannot emmit empty event');

        // act
        $this->driver->emmit($event_name, $event_content);
    }

    public function test_it_should_declare_bind_event_queue_and_return_consumer()
    {
        $app_name = 'pigeon.testing';
        $this->app['config']->set('pigeon.app_name', 'Pigeon Testing');
        $event_name = str_random(8);

        // setup
        $this->channel->shouldReceive('queue_declare')
            ->once()
            ->with("{$event_name}.{$app_name}", false, true, false, false, false, []);
        $this->channel->shouldReceive('exchange_declare')
            ->once()
            ->with(
                Driver::EVENT_EXCHANGE,
                'direct',
                false,
                true,
                false,
                false,
                false,
                Mockery::type(AMQPTable::class)
            );
        $this->channel->shouldReceive('queue_bind')
            ->once()
            ->with("{$event_name}.{$app_name}", Driver::EVENT_EXCHANGE, $event_name);

        // act
        $consumer = $this->driver
            ->events($event_name);

        // assert
        $this->assertInstanceOf(ConsumerContract::class, $consumer);
    }
}
