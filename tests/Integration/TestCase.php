<?php

namespace Convenia\Pigeon\Tests\Integration;

use Convenia\Pigeon\Tests\Support\ConnectsToRabbitMQ;
use Convenia\Pigeon\Tests\TestCase as BaseTestCase;
use Exception;

class TestCase extends BaseTestCase
{
    use ConnectsToRabbitMQ;

    protected $connection;

    protected $channel;

    protected $exchange = 'pigeon.integration.test';

    protected $exchange_type = 'direct';

    protected $routing_key = 'pigeon.test';

    protected $queue = 'pigeon.master';

    protected $alternative_queue = 'pigeon.test.alternative';

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->makeConnection();

        $this->channel = $this->connection->channel(1);
    }

    protected function tearDown(): void
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
