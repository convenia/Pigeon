<?php

namespace Convenia\AMQP\Tests;

use \Convenia\AMQP\Tests\TestCase;
use Convenia\AMQP\Drivers\RabbitDriver;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitDriverTest extends TestCase
{
    protected $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->driver = new RabbitDriver(
            $this->app,
            new AMQPStreamConnection(...$this->getConnectionAttributes())
        );
    }

    public function test_it_should_publish_a_message()
    {
        $this->driver->publish('test', [
            'foo' => 'fighters'
        ], 'some.queue');
    }

    /**
     * @return array connection attributes
     */
    private function getConnectionAttributes(): array
    {
        return [
            $this->app['config']['amqp.connection.host.address'],
            $this->app['config']['amqp.connection.host.port'],
            $this->app['config']['amqp.connection.credentials.user'],
            $this->app['config']['amqp.connection.credentials.password'],
            $this->app['config']['amqp.connection.host.vhost'],
            $insist = false,
            $login_method = 'AMQPLAIN',
            $login_response = null,
            $locale = 'en_US',
            (int) $this->app['config']['amqp.connection.read_timeout'],
            (bool) $this->app['config']['amqp.connection.keepalive'],
            (int) $this->app['config']['amqp.connection.write_timeout'],
            (int) $this->app['config']['amqp.connection.heartbeat'],
            $channel_rpc_timeout = 60,
        ];
    }
}
