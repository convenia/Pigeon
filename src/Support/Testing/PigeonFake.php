<?php

namespace Convenia\Pigeon\Support\Testing;

use Convenia\Pigeon\Consumer\Consumer;
use Convenia\Pigeon\Consumer\ConsumerContract;
use Convenia\Pigeon\Drivers\DriverContract;
use Convenia\Pigeon\PigeonManager;
use Convenia\Pigeon\Publisher\Publisher;
use Convenia\Pigeon\Publisher\PublisherContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\Assert as PHPUnit;

class PigeonFake extends PigeonManager implements DriverContract
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

    public function assertEmitted(string $category, array $data)
    {
        PHPUnit::assertTrue(
            $this->emitted($category, $data),
            "No event [$category] emitted with body"
        );
    }

    public function pushed(string $routing, array $message, $callback = null)
    {
        $callback = $callback ?: function ($publisher) use ($routing, $message) {
            return $publisher['routing'] === $routing
                && $publisher['exchange'] === $this->app['config']['pigeon.exchange']
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

        return $this->events->filter($callback)
            ->isNotEmpty();
    }

    public function rpcPushed(string $routing, array $message, $callback = null)
    {
        $callback = $callback ?: function ($publisher) use ($routing, $message) {
            return Str::contains($publisher['routing'], 'rpc.')
                && $publisher['routing'] === $routing
                && $publisher['exchange'] === $this->app['config']['pigeon.exchange']
                && isset($publisher['message'])
                && $publisher['message'] === $message;
        };

        return $this->pushed($routing, $message, $callback);
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

    public function assertCallbackReturn(string $queue, array $message, array $response)
    {
        // avoid tries to start a consumer on null queue
        $this->assertConsuming($queue);

        $reply_to = 'rpc.'.Str::random(5);
        $delivery_tag = Str::random(2);
        $message = new AMQPMessage(json_encode($message), ['reply_to' => $reply_to]);
        $message->delivery_info['channel'] = $this;
        $consumer = $this->consumers->get($queue);

        $message->delivery_info['delivery_tag'] = $delivery_tag;
        $exchange = $this->app['config']['pigeon.exchange'];
        $publisher = (new Publisher($this->app, $this, $exchange))->routing($reply_to);

        $this->publishers->push([
            'exchange' => $exchange,
            'routing' => $reply_to,
            'publisher' => $publisher,
        ]);

        $consumer->getCallback()->process($message);

        PHPUnit::assertTrue(
            $this->rpcPushed($reply_to, $response),
            'No RPC reply with defined body'
        );
    }

    public function queue(string $name): ConsumerContract
    {
        $consumer = new Consumer($this->app, $this, $name);
        $this->consumers->put($name, $consumer);

        return $consumer;
    }

    public function exchange(string $name, string $type): PublisherContract
    {
        return new Publisher($this->app, $this, $name);
    }

    public function routing(string $name): PublisherContract
    {
        $exchange = $this->app['config']['pigeon.exchange'];
        $publisher = (new Publisher($this->app, $this, $exchange))->routing($name);
        $this->publishers->push([
            'exchange' => $exchange,
            'routing' => $name,
            'publisher' => $publisher,
        ]);

        return $publisher;
    }

    public function events(string $event = '#'): ConsumerContract
    {
        $consumer = new Consumer($this->app, $this, $event);
        $this->consumers->put($event, $consumer);

        return $consumer;
    }

    public function emmit(string $eventName, array $event, array $meta = []): void
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
    }

    public function basic_consume()
    {
    }

    public function basic_publish(AMQPMessage $msg, $exchange, $routing)
    {
        $callback = function ($publisher) use ($exchange, $routing, $msg) {
            if ($publisher['routing'] === $routing
                && $publisher['exchange'] === $this->app['config']['pigeon.exchange']
                && ! isset($publisher['message'])
            ) {
                $publisher['message'] = json_decode($msg->body, true);

                return $publisher;
            }
        };

        $this->publishers = $this->publishers
            ->where('routing', $routing)
            ->map($callback);
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
    }

    public function wait()
    {
    }

    /**
     * @codeCoverageIgnore
     */
    public function getConnection()
    {
    }

    /**
     * @codeCoverageIgnore
     */
    public function getChannel(int $id = null)
    {
        return $this;
    }
}
