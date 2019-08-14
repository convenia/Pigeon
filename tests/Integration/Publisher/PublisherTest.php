<?php

namespace Convenia\Pigeon\Tests\Integration\Publisher;

use Convenia\Pigeon\Tests\Integration\TestCase;
use PhpAmqpLib\Message\AMQPMessage;

class PublisherTest extends TestCase
{
    /**
     * @var \Convenia\Pigeon\Drivers\Driver
     */
    protected $pigeon;

    protected function setUp()
    {
        parent::setUp();
        $this->pigeon = $this->app['pigeon']->driver('rabbit');
    }

    public function test_it_should_publish_a_message_using_exchange()
    {
        // setup
        $this->channel->exchange_declare($this->exchange, 'fanout');
        $this->channel->queue_declare($this->queue);
        $this->channel->queue_bind($this->queue, $this->exchange);
        $data = [
            'pigeon.foo' => 'dove.bar'
        ];

        // act
        $this->pigeon->exchange($this->exchange, 'fanout')
            ->publish($data);

        // assert
        $message = $this->channel->basic_get($this->queue);
        $this->assertEquals($data, json_decode($message->body, true));
    }

    public function test_it_should_publish_a_message_using_routing()
    {
        // setup
        $this->channel->exchange_declare($this->exchange, 'direct');
        $this->channel->queue_declare($this->queue);
        $data = [
            'pigeon.foo' => 'dove.bar'
        ];
        $this->app['config']->set('pigeon.exchange', $this->exchange);
        $this->app['config']->set('pigeon.exchange_type', $this->exchange_type);

        // act without binding (should not work)
        $this->pigeon->routing($this->routing_key)
            ->publish($data);

        // assert
        $message = $this->channel->basic_get($this->queue);
        $this->assertNull($message);

        // setup
        $this->channel->queue_bind($this->queue, $this->exchange, $this->routing_key);

        // act
        $this->pigeon->routing($this->routing_key)
            ->publish($data);

        // assert
        $message = $this->channel->basic_get($this->queue);
        $this->assertEquals($data, json_decode($message->body, true));
    }

    public function test_it_should_bind_exchange_and_queue()
    {
        // setup
        $this->channel->exchange_declare($this->exchange, 'direct');
        $this->channel->queue_declare($this->queue);
        $this->app['config']->set('pigeon.exchange', $this->exchange);
        $this->app['config']->set('pigeon.exchange_type', 'direct');

        // assert fail
        $received = $this->channel->basic_get($this->queue);
        $this->assertNull($received);

        // act
        $this->pigeon->routing($this->routing_key)
            ->bind($this->queue);

        // assert
        $msg_data = ['it' => 'should bind'];
        $msg = new AMQPMessage(json_encode($msg_data));
        $this->channel->basic_publish($msg, $this->exchange, $this->routing_key);

        $received = $this->channel->basic_get($this->queue);
        $this->assertEquals($msg_data, json_decode($received->body, true));
    }
}