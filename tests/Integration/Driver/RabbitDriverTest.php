<?php

namespace Convenia\Pigeon\Tests\Integration\Driver;

use Convenia\Pigeon\Events\Connected;
use Convenia\Pigeon\Tests\Integration\TestCase;
use Illuminate\Support\Facades\Event;

class RabbitDriverTest extends TestCase
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
        Event::fake();
        // setup
        $con = $this->pigeon->driver('rabbit')->getConnection();
        // assert
        $this->assertTrue($con->isConnected());
        $con->close();
        // assert
        $this->assertFalse($con->isConnected());
        // act
        $con = $this->pigeon->driver('rabbit')->getConnection();
        // assert
        $this->assertTrue($con->isConnected());
        
        Event::assertDispatched(Connected::class, 2);
    }
}
