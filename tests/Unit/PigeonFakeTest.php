<?php

namespace Convenia\Pigeon\Tests\Unit;

use Convenia\Pigeon\Consumer\Consumer;
use Convenia\Pigeon\Facade\Pigeon;
use Convenia\Pigeon\Publisher\Publisher;
use Convenia\Pigeon\Resolver\ResolverContract;
use Convenia\Pigeon\Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Constraint\ExceptionMessage;
use PHPUnit\Framework\ExpectationFailedException;

class PigeonFakeTest extends TestCase
{
    use WithFaker;

    /** @var \Convenia\Pigeon\Support\Testing\PigeonFake */
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

    public function test_it_should_assert_a_queue_has_consumers_with_specific_timeout()
    {
        // setup
        $queue = 'some.test.queue';

        $this->fake
            ->queue($queue)
            ->callback(function () {
            })->consume(5);

        try {
            //check for a wrong timeout
            $this->fake->assertConsuming($queue, 3);

            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage("The queue [$queue] does not match consumer timeout"));
        }

        $this->fake->assertConsuming($queue, 5);
    }

    public function test_it_should_assert_a_queue_has_consumers_with_specific_multiplicity()
    {
        // setup
        $queue = 'some.test.queue';

        $this->fake
            ->queue($queue)
            ->callback(function () {
            })->consume(5, false);

        try {
            //check for a wrong multiplicity
            $this->fake->assertConsuming($queue, 5, true);

            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage("The queue [$queue] does not match consumer multiplicity"));
        }

        $this->fake->assertConsuming($queue, 5, false);
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

    public function test_it_should_assert_event_emitted()
    {
        // setup
        $category = 'some.event.category';
        $data = [
            'foo' => 'fighters',
        ];

        // act
        try {
            $this->fake->assertDispatched($category, $data);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage("No event [$category] emitted with body"));
        }
        $this->fake->dispatch($category, $data);

        $this->fake->assertDispatched($category, $data);
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

    public function test_it_should_assert_consuming_event_with_specific_timeout()
    {
        // setup
        $category = 'some.event.category';

        $this->fake->events($category)
        ->callback(function () {
        })->consume(5);

        // act
        try {
            $this->fake->assertConsumingEvent($category, 3);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage("The event [$category] does not match consumer timeout"));
        }

        $this->fake->assertConsumingEvent($category, 5);
    }

    public function test_it_should_assert_consuming_event_with_specific_multiplicity()
    {
        // setup
        $category = 'some.event.category';

        $this->fake->events($category)
        ->callback(function () {
        })->consume(5, false);

        // act
        try {
            $this->fake->assertConsumingEvent($category, 5, true);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage("The event [$category] does not match consumer multiplicity"));
        }

        $this->fake->assertConsumingEvent($category, 5, false);
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
            $resolver->ack();
            $run = true;
        })->consume();

        $this->fake->dispatchListener($queue, []);

        $this->assertTrue($run);
    }

    public function test_it_should_assert_event_not_dispatched()
    {
        // setup
        $category = 'some.event.category';

        $data = [
            'foo' => 'baz',
        ];

        // act
        try {
            $this->fake->dispatch($category, $data);
            $this->fake->assertNotDispatched($category, $data);

            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage("Event [$category] emitted with body: ".json_encode($data)));
        }

        $data = [
            'foo' => 'bar',
        ];

        $this->fake->assertNotDispatched($category, $data);
    }

    public function test_it_asserts_nothing_dispatched()
    {
        $this->fake->assertNothingDispatched();

        $this->fake->dispatch('some.dummy.queue', ['dummy-field' => 123]);

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed asserting that actual size 1 matches expected size 0.');

        $this->fake->assertNothingDispatched();
    }

    public function test_if_asserts_the_count_of_messages()
    {
        $howMany = 22;

        for ($i = $howMany; $i > 0; $i--) {
            $this->fake->dispatch(
                implode('.', $this->faker->words()),
                ['some-dummy-field' => $this->faker->sentence()]
            );
        }

        $this->fake->assertDispatchCount($howMany);

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed asserting that actual size 22 matches expected size 10.');

        $this->fake->assertDispatchCount(10);
    }
}
