<?php

namespace Convenia\Pigeon\Tests\Unit;

use Convenia\Pigeon\Consumer\ConsumerContract;
use Convenia\Pigeon\Drivers\Driver;
use Convenia\Pigeon\Publisher\PublisherContract;
use Convenia\Pigeon\Tests\TestCase;
use Illuminate\Support\Str;
use Mockery;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class DriverTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject | Driver
     */
    private $driver;

    private $channel;

    private $queue = 'some.queue';

    protected function setUp(): void
    {
        parent::setUp();
        $this->channel = Mockery::mock(AMQPChannel::class);
        $this->driver = $this->getMockForAbstractClass(Driver::class, [$this->app]);
        $this->driver->method('getChannel')->willReturn($this->channel);
    }

    public function test_it_should_declare_a_queue()
    {
        $props = ['some' => 'prop'];
        // setup and asserts
        $this->driver->expects($this->once())
            ->method('queueDeclare')
            ->with($this->queue, $props);

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

    public function test_it_should_use_default_exchange_if_name_not_provided_to_exchange_method()
    {
        $type = 'fanout';

        // setup and asserts
        $this->channel->shouldNotReceive('exchange_declare');

        // act
        $publisher = $this->driver->exchange('', $type);

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
            ->with($exchange, $type, false, true, false, false, false, Mockery::type(AMQPTable::class));
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
        $event_name = Str::random(8);
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
            Driver::EVENT_EXCHANGE_TYPE,
            false,
            true,
            false,
            false,
            false,
            Mockery::type(AMQPTable::class)
        )->once();

        // act
        $this->driver->dispatch($event_name, $event_content);
    }

    public function test_it_should_publish_event_with_headers()
    {
        // setup
        $event_name = Str::random(8);
        $event_content = [
            'foo' => 'fighters',
        ];
        $meta = [
            $key = 'auth_user' => $value = random_int(100, 21312),
        ];

        // assert
        $this->channel->shouldReceive('basic_publish')->withArgs(
            function ($message, $exchange, $event) use ($key, $value, $event_name) {
                $app_headers = $message->get('application_headers');

                return ($app_headers instanceof AMQPTable)
                    && array_key_exists($key, $app_headers->getNativeData())
                    && ($app_headers->getNativeData()[$key] === $value)
                    && ($event === $event_name)
                    && ($exchange === Driver::EVENT_EXCHANGE);
            }
        )->once();
        $this->channel->shouldReceive('exchange_declare')->with(
            Driver::EVENT_EXCHANGE,
            Driver::EVENT_EXCHANGE_TYPE,
            false,
            true,
            false,
            false,
            false,
            Mockery::type(AMQPTable::class)
        )->once();

        // act
        $this->driver->dispatch($event_name, $event_content, $meta);
    }

    public function test_it_should_not_publish_empty_event()
    {
        // setup
        $event_name = 'my.event.name';
        $event_content = [];

        // assert
        $this->expectExceptionMessage('Cannot dispatch empty event');

        // act
        $this->driver->dispatch($event_name, $event_content);
    }

    public function test_it_should_declare_bind_event_queue_and_return_consumer()
    {
        $app_name = 'pigeon.testing';
        $this->app['config']->set('pigeon.app_name', 'Pigeon Testing');
        $event_name = Str::random(8);

        // setup
        $this->driver->expects($this->once())
            ->method('queueDeclare')
            ->with("{$event_name}.{$app_name}", []);

        $this->channel->shouldReceive('exchange_declare')
            ->once()
            ->with(
                Driver::EVENT_EXCHANGE,
                Driver::EVENT_EXCHANGE_TYPE,
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

    public function test_it_should_return_props_without_user_defined()
    {
        // Setup
        $this->app['config']->set('pigeon.dead.exchange', $exchange = 'dead');
        $this->app['config']->set('pigeon.dead.routing_key', $routing = 'dead_routing');

        // act
        $props = $this->driver->getProps();

        // assert
        $this->assertInstanceOf(AMQPTable::class, $props);
        $this->assertEquals([
            'x-dead-letter-exchange' => $exchange,
            'x-dead-letter-routing-key' => $routing,
        ], $props->getNativeData());
    }

    public function test_it_should_return_props_with_user_defined()
    {
        // Setup
        $this->app['config']->set('pigeon.dead.exchange', $exchange = 'dead');
        $this->app['config']->set('pigeon.dead.routing_key', $routing = 'dead_routing');

        // act
        $props = $this->driver->getProps([
            'another_prop' => $propValue = 'some_value',
        ]);

        // assert
        $this->assertInstanceOf(AMQPTable::class, $props);
        $this->assertEquals([
            'x-dead-letter-exchange' => $exchange,
            'x-dead-letter-routing-key' => $routing,
            'another_prop' => $propValue,
        ], $props->getNativeData());
    }

    public function test_it_should_return_props_without_dead_letter()
    {
        // Setup
        $this->app['config']->set('pigeon.dead', null);

        // act
        $props = $this->driver->getProps();

        // assert
        $this->assertInstanceOf(AMQPTable::class, $props);
        $this->assertEquals([], $props->getNativeData());
    }
}
