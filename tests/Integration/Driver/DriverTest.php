<?php

namespace Convenia\Pigeon\Tests\Integration\Driver;

use Convenia\Pigeon\Drivers\Driver;
use Convenia\Pigeon\Tests\Integration\TestCase;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class DriverTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Convenia\Pigeon\Drivers\Driver
     */
    protected $driver;

    protected function setUp()
    {
        parent::setUp();
        $this->driver = $this->getMockForAbstractClass(Driver::class, [$this->app]);
        $this->driver->method('getConnection')->willReturn($this->connection);
        $this->driver->method('getChannel')->willReturn($this->channel);
        $this->channel->queue_declare($this->queue, false, true, false, false);
    }

    public function test_it_should_publish_event()
    {
        // setup
        $event_name = str_random(7);
        $event_content = [
            'foo' => 'fighters',
        ];
        $this->channel->exchange_declare(Driver::EVENT_EXCHANGE, 'direct', false, true, false, false, false, new AMQPTable([
            'x-dead-letter-exchange' => 'dead.letter',
        ]));
        $this->channel->queue_bind($this->queue, Driver::EVENT_EXCHANGE, $event_name);

        // act
        $this->driver->emmit($event_name, $event_content);

        sleep(1);

        // assert
        $event = $this->channel->basic_get($this->queue);
        $this->assertEquals($event_content, json_decode($event->body, true));
        $this->channel->exchange_delete(Driver::EVENT_EXCHANGE);
    }

    public function test_it_should_consume_event()
    {
        // setup
        $event_name = str_random(8);
        $event_content = [
            'foo' => 'fighters',
        ];
        $this->app['config']->set('pigeon.app_name', 'pigeon');
        $queue = "{$event_name}.pigeon";
        $this->channel->queue_declare($queue, false, true, false, false, false, []);
        $this->channel->basic_publish(new AMQPMessage(json_encode($event_content)), '', $queue);

        // act
        $this->driver->events($event_name)
            ->callback(function ($event) use ($event_content) {
                // assert
                $this->assertEquals($event_content, $event);
            })
            ->consume(2, false);

        // teardown
        $this->channel->queue_delete($queue);
    }

    public function makeConnection()
    {
        return new AMQPStreamConnection(
            $host = $this->app['config']['pigeon.connection.host.address'],
            $port = $this->app['config']['pigeon.connection.host.port'],
            $user = $this->app['config']['pigeon.connection.credentials.user'],
            $password = $this->app['config']['pigeon.connection.credentials.password'],
            $vhost = $this->app['config']['pigeon.connection.host.vhost'],
            $insist = false,
            $login_method = 'AMQPLAIN',
            $login_response = null,
            $locale = 'en_US',
            $connection_timeout = 3.0,
            $read_write_timeout = (int) $this->app['config']['pigeon.connection.read_timeout'],
            $context = null,
            $keepalive = (bool) $this->app['config']['pigeon.connection.keepalive'],
            $heartbeat = (int) $this->app['config']['pigeon.connection.heartbeat']
        );
    }
}