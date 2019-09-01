<?php

namespace Convenia\Pigeon\Tests\Integration\Consumer;

use PhpAmqpLib\Message\AMQPMessage;
use Convenia\Pigeon\Resolver\ResolverContract;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use Convenia\Pigeon\Tests\Integration\TestCase;

class ConsumerTest extends TestCase
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

    public function test_it_should_consume_a_queue()
    {
        // should create queue
        $consumer = $this->pigeon->queue($this->queue);
        // setup
        $msg_data = ['foo' => 'fighters', 'bar' => 'baz'];
        $msg = new AMQPMessage(json_encode($msg_data));
        $this->channel->basic_publish($msg, '', $this->queue);

        // assert
        $callback = function ($data) use ($msg_data) {
            $this->assertEquals($msg_data, $data);
        };

        // act
        $consumer->callback($callback)->consume(5, false);
    }

    public function test_it_should_throw_timeout_without_multiple()
    {
        // should create queue
        $consumer = $this->pigeon->queue($this->queue);
        // setup
        $this->expectException(AMQPTimeoutException::class);

        // assert
        $callback = function ($data, ResolverContract $resolver) {
        };

        // act
        $consumer->callback($callback)->consume(1, false);
    }

    public function test_it_should_throw_timeout_with_multiple()
    {
        // should create queue
        $consumer = $this->pigeon->queue($this->queue);
        // setup
        $this->expectException(AMQPTimeoutException::class);

        // assert
        $callback = function ($data, ResolverContract $resolver) {
        };

        // act
        $consumer->callback($callback)->consume(1);
    }

    protected function tearDown(): void
    {
        $this->channel->queue_delete($this->queue);
        $this->connection->close();
        parent::tearDown();
    }
}
