<?php

namespace Convenia\Pigeon\Tests\Unit;

use Convenia\Pigeon\Facade\Pigeon;
use Convenia\Pigeon\Tests\TestCase;
use Convenia\Pigeon\Consumer\Consumer;
use Convenia\Pigeon\Publisher\Publisher;
use Convenia\Pigeon\Resolver\ResolverContract;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\Constraint\ExceptionMessage;

class PigeonFakeTest extends TestCase
{
    protected $fake;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fake = Pigeon::fake();
    }

    public function test_it_should_return_itself_on_driver_call()
    {
        $driver = $this->fake->driver();
        $this->assertEquals($this->fake, $driver);
    }

    public function test_it_should_return_consumer_on_queue_call()
    {
        // setup
        $queue = 'some.test.queue';

        // act
        $con = $this->fake->queue($queue);

        // assert
        $this->assertInstanceOf(Consumer::class, $con);
    }

    public function test_it_should_call_consumer_callback()
    {
        // setup
        $queue = 'some.test.queue';
        $message = [
            'foo' => 'fighters',
        ];

        // assert
        try {
            $this->fake->dispatchConsumer($queue, $message);

            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage("The queue [$queue] has no consumer"));
        }

        // act
        $ran = false;
        $this->fake
            ->queue($queue)
            ->callback(function () use (&$ran) {
                $ran = true;
            })
            ->consume();

        // assert
        $this->fake->dispatchConsumer($queue, $message);
        $this->assertTrue($ran, "Queue [$queue] callback did not run");
    }

    public function test_it_should_call_consumer_fallback()
    {
        // setup
        $queue = 'some.test.queue';
        $message = [
            'foo' => 'fighters',
        ];

        // assert
        try {
            $this->fake->dispatchConsumer($queue, $message);

            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage("The queue [$queue] has no consumer"));
        }

        // act
        $fail = false;
        $this->fake
            ->queue($queue)
            ->callback(function ($data) use (&$fail, $message) {
                $fail = true;
                $this->assertEquals($data, $message);
                $this->fail('Callback failed');
            })
            ->fallback(function ($e, $messageInstance) use ($message) {
                $this->assertEquals($message, json_decode($messageInstance->body, true));
                $this->assertEquals('Callback failed', $e->getMessage(), 'Wrong fallback message');
            })
            ->consume();

        // assert
        $this->fake->dispatchConsumer($queue, $message);
    }

    public function test_it_should_return_publisher_on_exchange_call()
    {
        // setup
        $exchange = 'my.awesome.exchange';
        $type = 'direct';

        // act
        $pub = $this->fake->exchange($exchange, $type);

        // assert
        $this->assertInstanceOf(Publisher::class, $pub);
    }

    public function test_it_should_return_publisher_on_routing_call()
    {
        // setup
        $exchange = 'my.awesome.exchange';
        $type = 'direct';
        $routing = 'my.awesome.application';

        $this->app['config']->set('ampq.exchange', $exchange);
        $this->app['config']->set('ampq.exchange_type', $type);

        // act
        $pub = $this->fake->routing($routing);

        // assert
        $this->assertInstanceOf(Publisher::class, $pub);
    }

    public function test_it_should_assert_a_queue_has_consumers()
    {
        // setup
        $queue = 'some.test.queue';

        // assert
        try {
            $this->fake->assertConsuming($queue);

            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage("The queue [$queue] has no consumer"));
        }

        // act
        $this->fake
            ->queue($queue)
            ->callback(function () {
            })
            ->consume();

        // assert
        $this->fake->assertConsuming($queue);
    }

    public function test_it_should_assert_message_published_using_routing()
    {
        // setup
        $exchange = 'my.awesome.exchange';
        $type = 'direct';
        $routing = 'my.awesome.application';
        $data = [
            'foo' => 'fighters',
        ];

        $this->app['config']->set('pigeon.exchange', $exchange);
        $this->app['config']->set('pigeon.exchange_type', $type);

        // act
        try {
            $this->fake->assertPublished($routing, $data);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage("No message published in [$routing] with body"));
        }
        $this->fake->routing($routing)
            ->publish($data);

        $this->fake->assertPublished($routing, $data);
    }

    public function test_it_should_fail_assert_message_published_using_routing_wrong_body()
    {
        // setup
        $exchange = 'my.awesome.exchange';
        $type = 'direct';
        $routing = 'my.awesome.application';
        $data = [
            'foo' => 'fighters',
        ];

        $this->app['config']->set('pigeon.exchange', $exchange);
        $this->app['config']->set('pigeon.exchange_type', $type);

        // act
        try {
            $this->fake->assertPublished($routing, $data);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage("No message published in [$routing] with body"));
        }
        $this->fake->routing($routing)
            ->publish([
                'foo' => 'bar',
            ]);

        try {
            $this->fake->assertPublished($routing, $data);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage("No message published in [$routing] with body"));
        }
    }

    public function test_it_should_assert_message_published_using_rpc()
    {
        // setup
        $exchange = 'my.awesome.exchange';
        $type = 'direct';
        $routing = 'my.awesome.application';
        $data = [
            'foo' => 'fighters',
        ];

        $this->app['config']->set('pigeon.exchange', $exchange);
        $this->app['config']->set('pigeon.exchange_type', $type);

        // act
        try {
            $this->fake->assertPublished($routing, $data);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage("No message published in [$routing] with body"));
        }
        $this->fake->routing($routing)
            ->rpc($data);

        $this->fake->assertPublished($routing, $data);
    }

    public function test_it_should_assert_callback_response_for_message()
    {
        // setup
        $queue = 'my.awesome.queue';
        $data = [
            'foo' => 'fighters',
        ];
        $message = [
            'bar' => 'baz',
        ];

        // act
        try {
            $this->fake->assertCallbackReturn($queue, $message, $data);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage("The queue [$queue] has no consumer"));
        }
        try {
            $this->fake->queue($queue)
                ->callback(function ($message, ResolverContract $resolver) use ($data) {
                    $resolver->response([
                        'wrong' => 'response',
                    ]);
                })
                ->consume();
            $this->fake->assertCallbackReturn($queue, $message, $data);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('No RPC reply with defined body'));
        }

        $this->fake->queue($queue)
            ->callback(function ($message, ResolverContract $resolver) use ($data) {
                $resolver->response($data);
            })
            ->consume();

        $this->fake->assertCallbackReturn($queue, $message, $data);
    }

    public function test_it_should_assert_event_emitted()
    {
        // setup
        $category = 'some.event.category';
        $data = [
            'foo' => 'fighters',
        ];

        // act
        try {
            $this->fake->assertEmitted($category, $data);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage("No event [$category] emitted with body"));
        }
        $this->fake->emmit($category, $data);

        $this->fake->assertEmitted($category, $data);
    }

    public function test_it_should_assert_consuming_event()
    {
        // setup
        $category = 'some.event.category';

        // act
        try {
            $this->fake->assertConsumingEvent($category);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage("No event consumer for [$category] event"));
        }
        $this->fake->events($category)
            ->callback(function () {
            })
            ->consume();

        $this->fake->assertConsumingEvent($category);
    }

    public function test_it_should_call_event_callback()
    {
        // setup
        $event = 'some.test.event';
        $message = [
            'foo' => 'fighters',
        ];

        // assert
        try {
            $this->fake->dispatchListener($event, $message);

            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage("The event [$event] has no listeners"));
        }

        // act
        $ran = false;
        $this->fake
            ->events($event)
            ->callback(function ($msg) use (&$ran, $message) {
                $this->assertEquals($message, $msg);
                $ran = true;
            })
            ->consume();

        // assert
        $this->fake->dispatchListener($event, $message);
        $this->assertTrue($ran, "Event [$event] callback did not run");
    }

    public function test_message_should_have_channel_on_events_consumer_resolver()
    {
        // setup
        $queue = 'some.test.event';

        $run = false;

        $this->fake
        ->events($queue)
        ->callback(function ($event, ResolverContract $resolver) use (&$run) {
            $resolver->ack();
            $run = true;
        })->consume();

        $this->fake->dispatchConsumer($queue, []);

        $this->assertTrue($run);
    }

    public function test_message_should_have_channel_on_consumer_resolver()
    {
        // setup
        $queue = 'some.test.event';

        $run = false;

        $this->fake
        ->queue($queue)
        ->callback(function ($event, ResolverContract $resolver) use (&$run) {
            $event;
            $resolver->ack();
            $run = true;
        })->consume();

        $this->fake->dispatchListener($queue, []);

        $this->assertTrue($run);
    }
}
