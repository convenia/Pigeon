<?php

namespace Convenia\Pigeon\Tests\Integration\Consumer;

use Convenia\Pigeon\Events\Terminated;
use Convenia\Pigeon\Events\Terminating;
use Convenia\Pigeon\Resolver\ResolverContract;
use Convenia\Pigeon\Tests\Integration\TestCase;
use Illuminate\Support\Facades\Event;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;

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

    function getSignals() : array
    {
        return [
            [SIGTERM],
            [SIGQUIT],
            [SIGINT]
        ];    
    }

    /**
     * @dataProvider getSignals
     * 
     * @requires extension pcntl
     */
    public function test_it_should_handle_signals($signal)
    {
        Event::fake();

        // should create queue
        $consumer = $this->pigeon->queue($this->queue);
        $msg = new AMQPMessage(json_encode(['some' => 'data']));
        $this->channel->basic_publish($msg, '', $this->queue);

        $callback = function () use ($signal) {
            posix_kill(posix_getpid(), $signal);
        };

        $consumer->callback($callback)->consume(30, true);
        $this->assertTrue(true, 'It should not throw error.');

        Event::assertDispatched(Terminating::class);
        Event::assertDispatched(Terminated::class);
    }

    protected function tearDown(): void
    {
        $this->channel->queue_delete($this->queue);
        $this->connection->close();
        parent::tearDown();
    }
}
