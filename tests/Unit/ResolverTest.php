<?php

namespace Convenia\Pigeon\Tests\Unit;

use Convenia\Pigeon\Resolver\Resolver;
use Convenia\Pigeon\Tests\TestCase;
use Illuminate\Support\Str;
use Mockery;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class ResolverTest extends TestCase
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

    public function test_it_should_ack_message()
    {
        // setup
        $delivery_tag = Str::random(5);
        $message = new AMQPMessage();

        $message->delivery_info['delivery_tag'] = $delivery_tag;
        $message->delivery_info['channel'] = $this->channel;
        $resolver = new Resolver($message);

        //assert
        $this->channel->shouldReceive('basic_ack')
            ->with($delivery_tag);

        // act
        $resolver->ack();
    }

    public function test_it_should_reject_message_without_requeue()
    {
        // setup
        $delivery_tag = Str::random(5);
        $message = new AMQPMessage();
        $requeue = false;

        $message->delivery_info['delivery_tag'] = $delivery_tag;
        $message->delivery_info['channel'] = $this->channel;
        $resolver = new Resolver($message);

        //assert
        $this->channel->shouldReceive('basic_nack')
            ->with($delivery_tag, false, $requeue);

        // act
        $resolver->reject($requeue);
    }

    public function test_it_should_reject_message_with_requeue()
    {
        // setup
        $delivery_tag = Str::random(5);
        $message = new AMQPMessage();
        $requeue = true;

        $message->delivery_info['delivery_tag'] = $delivery_tag;
        $message->delivery_info['channel'] = $this->channel;
        $resolver = new Resolver($message);

        //assert
        $this->channel->shouldReceive('basic_nack')
            ->with($delivery_tag, false, $requeue);

        // act
        $resolver->reject($requeue);
    }

    public function test_it_should_return_message_headers()
    {
        // setup
        $definedHeaders = [
            'reply_to' => 'some.queue',
            'application_headers' => new AMQPTable($appHeaders = [
                'my' => 'header',
            ]),
        ];
        $message = new AMQPMessage([], $definedHeaders);

        // act
        $resolver = new Resolver($message);

        // assert
        $this->assertEquals($definedHeaders, $resolver->headers());
        $this->assertEquals($definedHeaders['reply_to'], $resolver->headers('reply_to'));
    }
}
