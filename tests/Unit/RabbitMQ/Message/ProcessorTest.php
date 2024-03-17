<?php

namespace Convenia\Pigeon\Tests\Unit\RabbitMQ\Message;

use Convenia\Pigeon\MessageResolver;
use Convenia\Pigeon\RabbitMQ\Message\Processor;
use Convenia\Pigeon\Tests\TestCase;
use Error;
use Exception;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Mockery;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

class ProcessorTest extends TestCase
{
    use WithFaker;

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

        $callback = function ($received, MessageResolver $resolver) use ($data) {
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
            $this->assertInstanceOf(MessageResolver::class, $resolver);
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
        // Setting up scenario...
        Config::set('pigeon.consumer.on_failure', 'ack');

        $tag = $this->faker->numberBetween(1, 12445);
        $body = ['foo' => 'bar'];

        $channel = $this->partialMock(AMQPChannel::class, function ($mock) use ($tag) {
            $mock->shouldReceive('basic_ack')->once()->with($tag);
        });

        $message = new AMQPMessage(json_encode($body));
        $message->setChannel($channel);
        $message->setDeliveryTag($tag);

        $exception = new Exception('Callback failing and no fallback set');
        $callback = function () use ($exception) {
            throw $exception;
        };

        // Executing scenario...
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
        // Setting up scenario...
        $tag = $this->faker->numberBetween(1, 12445);
        $data = ['foo' => 'bar'];

        $channel = $this->partialMock(AMQPChannel::class, function ($mock) use ($tag) {
            $mock->shouldReceive('basic_nack')->once()->with($tag, false, false);
        });

        $message = new AMQPMessage(json_encode($data));
        $message->setChannel($channel);
        $message->setDeliveryTag($tag);

        Config::set('pigeon.consumer.on_failure', 'reject');

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

        // Executing scenario...
        $processor = new Processor($this->driver, $callback);

        $processor->process($message);
    }
}
