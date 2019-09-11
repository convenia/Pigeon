<?php

namespace Convenia\Pigeon\Tests\Integration\Publisher;

use Illuminate\Support\Str;
use PhpAmqpLib\Wire\AMQPTable;
use Convenia\Pigeon\Drivers\Driver;
use PhpAmqpLib\Message\AMQPMessage;
use Convenia\Pigeon\Tests\Integration\TestCase;

class PublisherTest extends TestCase
{
    /**
     * @var \Convenia\Pigeon\Drivers\Driver
     */
    protected $pigeon;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pigeon = $this->app['pigeon']->driver('rabbit');
    }

    public function test_it_should_publish_a_message_using_exchange()
    {
        // setup
        $this->channel->exchange_declare($this->exchange, 'fanout', false, true, false, false, false, new AMQPTable([
            'x-dead-letter-exchange' => 'dead.letter',
        ]));
        $this->channel->queue_declare($this->queue);
        $this->channel->queue_bind($this->queue, $this->exchange);
        $data = [
            'pigeon.foo' => 'dove.bar',
        ];

        // act
        $this->pigeon->exchange($this->exchange, 'fanout')
            ->publish($data);

        // wait message go to broker
        sleep(1);

        // assert
        $message = $this->channel->basic_get($this->queue);
        $this->assertEquals($data, json_decode($message->body, true));
    }

    public function test_it_should_publish_a_message_using_routing()
    {
        // setup
        $this->channel->queue_declare($this->queue);
        $data = [
            'pigeon.foo' => 'dove.bar',
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

        sleep(1);

        // assert
        $message = $this->channel->basic_get($this->queue);
        $this->assertEquals($data, json_decode($message->body, true));
    }

    public function test_it_should_bind_exchange_and_queue()
    {
        // setup
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

    public function test_it_should_publish_a_remote_procedure_call()
    {
        // setup
        $this->channel->exchange_declare($this->exchange, 'fanout', false, true, false, false, false, new AMQPTable([
            'x-dead-letter-exchange' => 'dead.letter',
        ]));
        $this->channel->queue_declare($this->queue);
        $this->channel->queue_bind($this->queue, $this->exchange);
        $this->app['config']->set('pigeon.exchange', $this->exchange);
        $this->app['config']->set('pigeon.exchange_type', 'direct');
        $data = [
            'pigeon.foo' => 'dove.bar',
        ];

        // act
        $reply_to = $this->pigeon->exchange($this->exchange, 'fanout')
            ->rpc($data);

        // wait message publish and respond
        sleep(1);
        $message = $this->channel->basic_get($this->queue);
        $this->assertEquals($reply_to, $message->get('reply_to'));
        $this->assertEquals($data, json_decode($message->body, true));
        $this->assertTrue(Str::contains($reply_to, 'amq'));
        $this->channel->queue_delete($reply_to);
    }

    public function test_it_should_publish_event()
    {
        // setup
        $event_queue = "event.$this->queue";
        $event_name = 'event.testing.event.sourcing';
        $event_data = ['it' => 'should bind'];

        $this->channel->exchange_declare(Driver::EVENT_EXCHANGE, Driver::EVENT_EXCHANGE_TYPE, false, true, false, false, false, new AMQPTable([
            'x-dead-letter-exchange' => 'dead.letter',
        ]));
        $this->channel->queue_declare($event_queue);
        $this->channel->queue_bind($event_queue, Driver::EVENT_EXCHANGE, $event_name);

        // assert fail
        $received = $this->channel->basic_get($event_queue);
        $this->assertNull($received);

        // act
        $this->pigeon->emmit($event_name, $event_data);

        sleep(1);
        // assert
        $received = $this->channel->basic_get($event_queue);
        $this->assertEquals($event_data, json_decode($received->body, true));

        // teardown
        $this->channel->queue_delete($event_queue);
        $this->channel->exchange_delete(Driver::EVENT_EXCHANGE);
    }
}
