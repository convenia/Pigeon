<?php

namespace Convenia\Pigeon\Tests\Integration;

use Exception;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Convenia\Pigeon\Tests\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected $connection;

    protected $channel;

    protected $exchange = 'pigeon.integration.test';

    protected $exchange_type = 'direct';

    protected $routing_key = 'pigeon.test';

    protected $queue = 'pigeon.master';

    protected $alternative_queue = 'pigeon.test.alternative';

    protected function setUp()
    {
        parent::setUp();
        $this->connection = new AMQPStreamConnection(
            $host = env('PIGEON_ADDRESS'),
            $port = env('PIGEON_PORT'),
            $user = env('PIGEON_USER'),
            $password = env('PIGEON_PASSWORD'),
            $vhost = env('PIGEON_VHOST'),
            $insist = false,
            $login_method = 'AMQPLAIN',
            $login_response = null,
            $locale = 'en_US',
            $connection_timeout = 3.0,
            $read_write_timeout = env('PIGEON_READ_TIMEOUT', 130),
            $context = null,
            $keepalive = env('PIGEON_KEEPALIVE', true),
            $heartbeat = env('PIGEON_HEARTBEAT', 10)
        );
        $this->channel = $this->connection->channel(1);
    }

    protected function tearDown()
    {
        try {
            $this->channel->queue_delete($this->queue);
        } catch (Exception $e) {
        }
        try {
            $this->channel->queue_delete($this->alternative_queue);
        } catch (Exception $e) {
        }
        try {
            $this->channel->exchange_delete($this->exchange);
        } catch (Exception $e) {
        }
        $this->connection->close();
        parent::tearDown();
    }
}
