<?php

namespace Convenia\Pigeon\Tests\Unit;

use Convenia\Pigeon\Publisher\Publisher;
use Convenia\Pigeon\Tests\TestCase;
use Mockery;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class PublisherTest extends TestCase
{
    protected $driver;

    protected $channel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->channel = Mockery::mock(AMQPChannel::class);
        $this->driver = Mockery::mock('Convenia\Pigeon\Drivers\RabbitDriver');

        $this->driver->shouldReceive('getChannel')->andReturn($this->channel);
    }

    public function test_it_should_publish_message_without_routing_key_and_merge_user_properties_with_default_properties()
    {
        // setup
        $exchange = 'my.awesome.exchange';
        $data = [
            'foo' => 'fighters',
        ];
        $props = [
            'priority' => 10,
        ];
        $publisher = new Publisher($this->app, $this->driver, $exchange);

        // assert
        $this->channel->shouldReceive('basic_publish')->with(
            Mockery::on(function ($message) {
                $body = json_decode($message->getBody());

                return 60000000 === $message->get('expiration')
                    && 10 === $message->get('priority')
                    && 'fighters' === $body->foo;
            }),
            $exchange,
            null
        )->once();

        // act
        $publisher->publish($data, $props);
    }

    public function test_it_should_publish_message_with_routing_key_with_props()
    {
        // setup
        $exchange = 'my.awesome.exchange';
        $routing = 'my.awesome.service';
        $data = [
            'foo' => 'fighters',
        ];
        $props = [
            'priority' => 10,
        ];
        $publisher = new Publisher($this->app, $this->driver, $exchange);

        // assert
        $this->channel->shouldReceive('basic_publish')->with(
            Mockery::type(AMQPMessage::class),
            $exchange,
            $routing
        )->once();

        // act
        $publisher->routing($routing)->publish($data, $props);
    }

    public function test_it_should_create_new_publisher_on_new_routing()
    {
        // setup
        $exchange = 'my.awesome.exchange';
        $first_routing = 'my.first.awesome.service';
        $second_routing = 'my.second.awesome.service';
        $data = [
            'foo' => 'fighters',
        ];
        $props = [
            'priority' => 10,
        ];

        $first_publisher = (new Publisher($this->app, $this->driver, $exchange))
            ->routing($first_routing);
        $second_publisher = (new Publisher($this->app, $this->driver, $exchange))
            ->routing($second_routing);

        // assert
        $this->channel->shouldReceive('basic_publish')->with(
            Mockery::type(AMQPMessage::class),
            $exchange,
            $first_routing
        )->once();
        $this->channel->shouldReceive('basic_publish')->with(
            Mockery::type(AMQPMessage::class),
            $exchange,
            $second_routing
        )->once();

        // act
        $first_publisher->publish($data, $props);
        $second_publisher->publish($data, $props);
    }

    public function test_it_should_add_all_headers()
    {
        // setup
        $exchange = 'my.awesome.exchange';
        $routing = 'my.awesome.service';
        $data = [
            'foo' => 'fighters',
        ];
        $headers = [
            'foo_bar' => 'baz',
            'foo' => 'fighters',
            'deep' => [
                'level' => 1,
            ],
        ];
        $this->app['config']->set('pigeon.headers', $configHeaders = [
            'my' => 'user',
        ]);

        $publisher = new Publisher($this->app, $this->driver, $exchange);

        // assert
        $this->channel->shouldReceive('basic_publish')->with(
            Mockery::on(function (AMQPMessage $arg) use ($headers, $configHeaders) {
                $app_headers = $arg->get('application_headers');

                return ($app_headers instanceof AMQPTable)
                    && (array_merge($configHeaders, $headers) === $app_headers->getNativeData());
            }),
            $exchange,
            null
        )->once();

        // act
        foreach ($headers as $key => $value) {
            $publisher->header($key, $value);
        }
        $publisher->publish($data);
    }

    public function test_it_should_use_callable_value()
    {
        $publisher = new Publisher($this->app, $this->driver, 'exg');
        $this->app['config']->set('pigeon.headers', $headers = [
            'string' => 'my string',
            'callable' => function () {
                return 'my callable';
            },
        ]);
        $this->assertSame([
            'string' => 'my string',
            'callable' => 'my callable',
        ], $publisher->getHeaders());
    }
}
