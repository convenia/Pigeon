<?php

namespace Convenia\Pigeon\Tests\Integration\Driver;

use Convenia\Pigeon\Tests\Integration\TestCase;

class RabbitMQDriverTest extends TestCase
{
    /**
     * @var \Convenia\Pigeon\PigeonManager
     */
    protected $pigeon;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pigeon = $this->app['pigeon'];
    }

    public function test_it_should_reconnect_if_connection_close()
    {
        // setup
        $con = $this->pigeon->driver()->connection();
        // assert
        $this->assertTrue($con->isConnected());
        $con->close();
        // assert
        $this->assertFalse($con->isConnected());
        // act
        $con = $this->pigeon->driver()->connection();
        // assert
        $this->assertTrue($con->isConnected());
    }
}
