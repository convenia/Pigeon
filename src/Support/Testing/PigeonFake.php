<?php

namespace Convenia\Pigeon\Support\Testing;

use Convenia\Pigeon\RabbitMQ\Consumer;
use Convenia\Pigeon\Contracts\Consumer as ConsumerContract;
use Convenia\Pigeon\Contracts\Driver;
use Convenia\Pigeon\Contracts\Publisher as PublisherContract;
use Convenia\Pigeon\PigeonManager;
use Convenia\Pigeon\RabbitMQ\Publisher;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\Assert as PHPUnit;

class PigeonFake extends PigeonManager implements Driver
{
    public $callbacks = [];

    protected $consumers;

    protected $publishers;

    protected $events;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->consumers = new Collection();
        $this->publishers = new Collection();
        $this->events = new Collection();
    }

    public function assertConsuming(string $queue, int $timeout = null, bool $multiple = null)
    {
        $comsumer = $this->consumers->get($queue);

        PHPUnit::assertNotNull(
            $comsumer,
            "The queue [$queue] has no consumer"
        );

        if (! is_null($timeout)) {
            PHPUnit::assertEquals(
                $timeout,
                $comsumer->timeout,
                "The queue [$queue] does not match consumer timeout"
            );
        }

        if (! is_null($multiple)) {
            PHPUnit::assertEquals(
                $multiple,
                $comsumer->multiple,
                "The queue [$queue] does not match consumer multiplicity"
            );
        }
    }

    public function assertConsumingEvent(string $event, int $timeout = null, bool $multiple = null)
    {
        $comsumer = $this->consumers->get($event);

        PHPUnit::assertNotNull(
            $comsumer,
            "No event consumer for [$event] event"
        );

        if (! is_null($timeout)) {
            PHPUnit::assertEquals(
                $timeout,
                $comsumer->timeout,
                "The event [$event] does not match consumer timeout"
            );
        }

        if (! is_null($multiple)) {
            PHPUnit::assertEquals(
                $multiple,
                $comsumer->multiple,
                "The event [$event] does not match consumer multiplicity"
            );
        }
    }

    public function assertPublished(string $routing, array $message)
    {
        PHPUnit::assertTrue(
            $this->pushed($routing, $message),
            "No message published in [$routing] with body"
        );
    }

    public function assertDispatched(string $category, array $data)
    {
        PHPUnit::assertTrue(
            $this->emitted($category, $data),
            "No event [$category] emitted with body"
        );
    }

    public function assertNotDispatched(string $category, array $data)
    {
        PHPUnit::assertFalse(
            $this->emitted($category, $data),
            "Event [$category] emitted with body: ".json_encode($data)
        );
    }

    /**
     * Checks if none message was dispatched.
     *
     * @return void
     */
    public function assertNothingDispatched()
    {
        $this->assertDispatchCount(0);
    }

    /**
     * Checks if the expected quantity of messages was dispatched.
     *
     * @param  int  $count
     * @return void
     */
    public function assertDispatchCount(int $count)
    {
        PHPUnit::assertCount($count, $this->events);
    }

    public function pushed(string $routing, array $message, $callback = null)
    {
        $callback = $callback ?: function ($publisher) use ($routing, $message) {
            return $publisher['routing'] === $routing
                && $publisher['exchange'] === $this->config['pigeon.exchange']
                && isset($publisher['message'])
                && $publisher['message'] === $message;
        };

        return $this->publishers
            ->where('routing', $routing)
            ->filter($callback)->isNotEmpty();
    }

    public function emitted(string $event, array $data, $callback = null)
    {
        $callback = $callback ?: function ($e) use ($event, $data) {
            return ($e['event'] == $event) && ($e['data'] == $data);
        };

        return $this->events->filter($callback)->isNotEmpty();
    }

    public function dispatchConsumer(string $queue, array $message, array $props = [])
    {
        // avoid tries to start a consumer on null queue
        $this->assertConsuming($queue);

        $message = new AMQPMessage(json_encode($message), $props);
        $message->delivery_info['channel'] = $this;
        $message->delivery_info['delivery_tag'] = Str::random(3);
        $consumer = $this->consumers->get($queue);
        $consumer->getCallback()->process($message);
    }

    public function dispatchListener(string $event, array $message)
    {
        // avoid tries to start a consumer on null queue
        PHPUnit::assertTrue(
            $this->consumers->has($event),
            "The event [$event] has no listeners"
        );

        $message = new AMQPMessage(json_encode($message));
        $message->delivery_info['channel'] = $this;
        $message->delivery_info['delivery_tag'] = Str::random(3);
        $consumer = $this->consumers->get($event);
        $consumer->getCallback()->process($message);
    }

    /**
     * Creates a consumer with a queue.
     *
     * @param  string  $name
     * @param  array  $properties
     *
     * @return ConsumerContract
     */
    public function queue(string $name, array $properties = []): ConsumerContract
    {
        $consumer = new Consumer($this->container, $this, $name);
        $this->consumers->put($name, $consumer);

        return $consumer;
    }

    /**
     * Created a new publisher defining a exchange.
     *
     * @param  string  $name
     * @param  string  $type
     *
     * @return PublisherContract
     *
     * @deprecated Use routing() function instead.
     */
    public function exchange(string $name, string $type = 'direct'): PublisherContract
    {
        return new Publisher($this->container, $this, $name);
    }

    /**
     * Creates a publisher for a routing key and using the app's configured exchange.
     *
     * @param  string  $name
     *
     * @return PublisherContract
     */
    public function routing(string $name = null): PublisherContract
    {
        $exchange = $this->config['pigeon.exchange'];
        $publisher = (new Publisher($this->container, $this, $exchange))->routing($name);
        $this->publishers->push([
            'exchange' => $exchange,
            'routing' => $name,
            'publisher' => $publisher,
        ]);

        return $publisher;
    }

    public function events(string $event = '#'): ConsumerContract
    {
        $consumer = new Consumer($this->container, $this, $event);
        $this->consumers->put($event, $consumer);

        return $consumer;
    }

    public function dispatch(string $eventName, array $event, array $meta = []): void
    {
        $this->events->push([
            'event' => $eventName,
            'data' => $event,
        ]);
    }

    public function driver($driver = null)
    {
        return $this;
    }

    public function basic_qos()
    {
        //
    }

    public function basic_consume()
    {
        //
    }

    public function basic_publish(AMQPMessage $msg, $exchange, $routing)
    {
        $this->publishers = $this->publishers
            ->map(function ($publisher) use ($msg) {
                if (! isset($publisher['message'])) {
                    $publisher['message'] = json_decode($msg->body, true);

                    return $publisher;
                }

                return $publisher;
            });
    }

    public function queue_declare($queue = '')
    {
        if (empty($queue)) {
            return [Str::random(7), null, null];
        }

        return [$queue, null, null];
    }

    public function basic_ack()
    {
        //
    }

    public function wait()
    {
        //
    }

    /**
     * @codeCoverageIgnore
     */
    public function connection()
    {
        //
    }

    /**
     * @codeCoverageIgnore
     */
    public function getChannel(int $id = null)
    {
        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function queue_bind(string $queue, string $exchange = '', string $routing = '')
    {
        return $this;
    }

    /**
     * Closes the connection disgracefully.
     *
     * @return void
     */
    public function quitHard(): void
    {
        //
    }

    /**
     * Closes the connection gracefully.
     *
     * @return void
     */
    public function quit(): void
    {
        //
    }

    public function queueDeclare(string $name, array $properties)
    {
        //
    }
}
