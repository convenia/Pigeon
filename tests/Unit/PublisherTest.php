<?php

namespace Convenia\Pigeon\Tests\Unit;

use Mockery;
use Convenia\Pigeon\Tests\TestCase;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Convenia\Pigeon\Publisher\Publisher;

class PublisherTest extends TestCase
{
    protected $driver;

    protected $channel;

    protected function setUp()
    {
        parent::setUp();
        $this->channel = Mockery::mock(AMQPChannel::class);
        $this->driver = Mockery::mock('Convenia\Pigeon\Drivers\RabbitDriver');

        $this->driver->shouldReceive('getChannel')->andReturn($this->channel);
    }

    public function test_it_should_publish_message_without_routing_key_with_props()
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

    public function test_it_should_publish_a_message_with_reply_queue()
    {
        // setup
        $exchange = 'my.awesome.exchange';
        $routing = 'my.awesome.service';
        $response_via = 'amq.asodhoafdsfds89sd87612h1781831_123asd';
        $outgoing = [
            'foo' => 'fighters',
        ];
        $incoming = [
            'my' => 'response',
        ];
        $props = [
            'priority' => 10,
        ];
        $publisher = new Publisher($this->app, $this->driver, $exchange);

        // assert
        $this->channel->shouldReceive('queue_declare')->once()->andReturn([$response_via, null, null]);
        $this->channel->shouldReceive('basic_publish')->with(
            Mockery::type(AMQPMessage::class),
            $exchange,
            $routing
        )->once();

        // act
        $reply_to = $publisher->routing($routing)->rpc($outgoing, $props);

        // assert
        $this->assertEquals($response_via, $reply_to);
    }
}
