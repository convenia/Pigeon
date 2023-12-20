<?php

namespace Convenia\Pigeon\Tests\Unit\RabbitMQ\Message;

use Convenia\Pigeon\Contracts\Resolver;
use Convenia\Pigeon\RabbitMQ\Message\Processor;
use Convenia\Pigeon\Tests\TestCase;
use Error;
use Exception;
use Illuminate\Support\Facades\Log;
use Mockery;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

class ProcessorTest extends TestCase
{
    protected $driver;

    protected $channel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->channel = Mockery::mock(AMQPChannel::class);
        $this->driver = Mockery::mock('Convenia\Pigeon\Drivers\RabbitMQDriver');
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
        $processor = new Processor($this->driver, $callback);
        $processor->process($message);
    }

    public function test_it_should_call_user_callback_with_resolver()
    {
        // setup
        $data = ['foo' => 'bar'];
        $reply_to = 'some.queue';
        $message = new AMQPMessage(json_encode($data));

        $callback = function ($received, Resolver $resolver) use ($data) {
            // assert
            $this->assertEquals($data, $received);
        };

        // act
        $processor = new Processor($this->driver, $callback);
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
        $processor = new Processor($this->driver, $callback, $fallback);
        $processor->process($message);
    }

    public function test_it_should_call_user_fallback_with_resolver()
    {
        // setup
        $ran = false;
        $message = new AMQPMessage();
        $exception = 'Testing user fallback';
        $callback = function ($received) use ($exception) {
            $this->fail($exception);
        };
        $fallback = function ($e, $received, $resolver) use ($exception, $message, &$ran) {
            $ran = true;
            $this->assertEquals($e->getMessage(), $exception);
            $this->assertEquals($received, $message);
            $this->assertInstanceOf(Resolver::class, $resolver);
        };

        // act
        $processor = new Processor($this->driver, $callback, $fallback);
        $processor->process($message);
        $this->assertTrue($ran, 'Test did not run');
    }

    public function test_it_should_have_default_fallback_throw_exception_on_unexpected_config()
    {
        // setup
        $data = ['foo' => 'bar'];
        $message = new AMQPMessage(json_encode($data));
        $this->app['config']['pigeon.consumer.on_failure'] = 'unexpected_config';

        $exception = new Exception('Callback failing and no fallback set');
        $callback = function () use ($exception) {
            throw $exception;
        };

        $this->expectExceptionMessage('Callback failing and no fallback set');
        // act
        $processor = new Processor($this->driver, $callback);

        Log::shouldReceive('error')->once()->with(
            $exception->getMessage(),
            [
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine(),
                'tracing'  => $exception->getTraceAsString(),
                'previous' => $exception->getPrevious(),
                'message'  => json_decode($message->body, true),
            ]
        );

        $processor->process($message);
    }

    public function test_it_should_have_default_fallback_throw_errors_on_unexpected_config()
    {
        // setup
        $data = ['foo' => 'bar'];
        $message = new AMQPMessage(json_encode($data));
        $this->app['config']['pigeon.consumer.on_failure'] = 'unexpected_config';

        $exception = new Error('Testing errors');
        $callback = function () use ($exception) {
            throw $exception;
        };

        $this->expectExceptionMessage('Testing errors');
        // act
        $processor = new Processor($this->driver, $callback);

        Log::shouldReceive('error')->once()->with(
            $exception->getMessage(),
            [
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine(),
                'tracing'  => $exception->getTraceAsString(),
                'previous' => $exception->getPrevious(),
                'message'  => json_decode($message->body, true),
            ]
        );

        $processor->process($message);
    }

    public function test_it_should_have_default_fallback_ack_on_configured()
    {
        // setup
        $data = ['foo' => 'bar'];
        $message = new AMQPMessage(json_encode($data));
        $channel = Mockery::mock(AMQPChannel::class);
        $message->delivery_info['channel'] = $channel;
        $message->delivery_info['delivery_tag'] = $tag = random_int(1, 12445);
        $this->app['config']['pigeon.consumer.on_failure'] = 'ack';
        $channel->shouldReceive('basic_ack')
            ->with($tag)->once();

        $exception = new Exception('Callback failing and no fallback set');
        $callback = function () use ($exception) {
            throw $exception;
        };

        // act
        $processor = new Processor($this->driver, $callback);

        Log::shouldReceive('error')->with(
            $exception->getMessage(),
            [
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine(),
                'tracing'  => $exception->getTraceAsString(),
                'previous' => $exception->getPrevious(),
                'message'  => json_decode($message->body, true),
            ]
        );
        $processor->process($message);
    }

    public function test_it_should_have_default_fallback_reject_on_configured()
    {
        // setup
        $data = ['foo' => 'bar'];
        $message = new AMQPMessage(json_encode($data));
        $channel = Mockery::mock(AMQPChannel::class);
        $message->delivery_info['channel'] = $channel;
        $message->delivery_info['delivery_tag'] = $tag = random_int(1, 12445);
        $this->app['config']['pigeon.consumer.on_failure'] = 'reject';
        $channel->shouldReceive('basic_nack')
            ->with($tag, false, false)
            ->once();

        $exception = new Exception('Callback failing and no fallback set');
        $callback = function () use ($exception) {
            throw $exception;
        };
        Log::shouldReceive('error')->with(
            $exception->getMessage(),
            [
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine(),
                'tracing'  => $exception->getTraceAsString(),
                'previous' => $exception->getPrevious(),
                'message'  => json_decode($message->body, true),
            ]
        );

        // act
        $processor = new Processor($this->driver, $callback);

        $processor->process($message);
    }
}
