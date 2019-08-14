<?php

namespace Convenia\Pigeon\Tests\Unit;

use Convenia\Pigeon\MessageProcessor\MessageProcessor;
use Convenia\Pigeon\Resolver\ResolverContract;
use Convenia\Pigeon\Tests\TestCase;
use Exception;
use Illuminate\Support\Facades\Log;
use Mockery;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\Constraint\ExceptionMessage;

class MessageProcessorTest extends TestCase
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

    public function test_it_should_call_user_callback_without_resolver()
    {
        // setup
        $data = ['foo' => 'bar'];
        $message = new AMQPMessage(json_encode($data));
        $callback = function ($received) use ($data) {
            // assert
            $this->assertEquals($data, $received);
        };

        // act
        $processor = new MessageProcessor($this->driver, $callback);
        $processor->process($message);
    }

    public function test_it_should_call_user_callback_with_resolver()
    {
        // setup
        $data = ['foo' => 'bar'];
        $reply_to = 'some.queue';
        $message = new AMQPMessage(json_encode($data));

        $callback = function ($received, ResolverContract $resolver) use ($data) {
            // assert
            $this->assertEquals($data, $received);
        };

        // act
        $processor = new MessageProcessor($this->driver, $callback);
        $processor->process($message);
    }

    public function test_it_should_call_user_fallback_if_callback_fail()
    {
        // setup
        $message = new AMQPMessage();
        $exception = 'Testing user fallback';
        $callback = function ($received) use ($exception) {
            $this->fail($exception);
        };
        $fallback = function ($e, $received) use ($exception, $message) {
            $this->assertEquals($e->getMessage(), $exception);
            $this->assertEquals($received, $message);
        };

        // act
        $processor = new MessageProcessor($this->driver, $callback, $fallback);
        $processor->process($message);
    }

    public function test_it_should_have_default_fallback()
    {
        // setup
        $data = ['foo' => 'bar'];
        $message = new AMQPMessage(json_encode($data));
        $callback = function () {
            $this->fail('Callback failing and no fallback set');
        };

        // act
        $processor = new MessageProcessor($this->driver, $callback);

        try {
            $processor->process($message);
            $this->fail();
        } catch (Exception $e) {
            Log::shouldReceive('error')->with(
                $e->getMessage(),
                [
                    'file'     => $e->getFile(),
                    'line'     => $e->getLine(),
                    'tracing'  => $e->getTraceAsString(),
                    'previous' => $e->getPrevious(),
                    'message'  => json_decode($message->body, true),
                ]
            );
            $this->assertThat($e, new ExceptionMessage('Callback failing and no fallback set'));
        }
    }
}
