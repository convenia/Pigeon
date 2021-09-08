<?php

namespace Convenia\Pigeon\Tests\Integration\Resolver;

//define('AMQP_DEBUG', true);

use Convenia\Pigeon\Resolver\Resolver;
use Convenia\Pigeon\Tests\Integration\TestCase;
use Illuminate\Support\Str;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class ResolverTest extends TestCase
{
    protected $pigeon;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pigeon = $this->app['pigeon']->driver('rabbit');
    }

    public function test_it_should_ack_message()
    {
        // setup
        $msg_data = ['foo' => 'fighters', 'bar' => 'baz'];
        $msg = new AMQPMessage(json_encode($msg_data));
        $this->channel->queue_declare($this->queue, $passive = false, $durable = true, $exclusive = false, $auto_delete = false);
        $this->channel->basic_publish($msg, '', $this->queue);

        // act
        $this->channel->basic_consume(
            $this->queue,
            'pigeon.integration.test',
            false,
            false,
            false,
            false,
            function ($request_message) {
                $resolver = new Resolver($request_message);
                $resolver->ack();
            }
        );
        $this->channel->wait(null, null, 2);
        $this->channel->basic_cancel('pigeon.integration.test');

        // assert
        $timeout = 2;
        $this->expectExceptionMessage("The connection timed out after $timeout sec while awaiting incoming data");
        $this->channel->basic_consume(
            $this->queue,
            'pigeon.integration.test.s',
            false,
            false,
            false,
            false,
            function () {
                $this->assertTrue(false, 'Queue should not have message.');
            }
        );
        $this->channel->wait(null, null, $timeout);
    }

    public function test_it_should_get_message_headers()
    {
        // setup
        $msg_data = ['foo' => 'fighters', 'bar' => 'baz'];
        $headers = [
            'application_headers' => new AMQPTable([
                'my' => 'header',
                'deep' => [
                    'header' => 'level',
                ],
            ]),
            'correlation_id' => Str::random(16),
        ];
        $msg = new AMQPMessage(json_encode($msg_data), $headers);
        $this->channel->queue_declare($this->queue, $passive = false, $durable = true, $exclusive = false, $auto_delete = false);
        $this->channel->basic_publish($msg, '', $this->queue);

        // act
        $this->channel->basic_consume(
            $this->queue,
            'pigeon.integration.test',
            false,
            false,
            false,
            false,
            function ($request_message) use ($headers) {
                $resolver = new Resolver($request_message);
                $this->assertEquals($headers, $resolver->headers());
            }
        );
        $this->channel->wait(null, null, 2);
        $this->channel->basic_cancel('pigeon.integration.test');

        $timeout = 2;
        $this->expectExceptionMessage("The connection timed out after $timeout sec while awaiting incoming data");
        $this->channel->basic_consume(
            $this->queue,
            'pigeon.integration.test.s',
            false,
            false,
            false,
            false,
            function () {
                $this->assertTrue(false, 'Queue should not have message.');
            }
        );
        $this->channel->wait(null, null, $timeout);
    }

    protected function tearDown(): void
    {
        $this->pigeon->getConnection()->close();
        parent::tearDown();
    }
}
