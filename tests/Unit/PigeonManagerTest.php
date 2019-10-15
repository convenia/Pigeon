<?php

namespace Convenia\Pigeon\Tests\Unit;

use Convenia\Pigeon\Exceptions\Driver\NullDriverException;
use Convenia\Pigeon\PigeonManager;
use Convenia\Pigeon\Tests\TestCase;

class PigeonManagerTest extends TestCase
{
    public function test_it_should_return_default_driver_based_on_config_or_null()
    {
        // setup
        $manager = new PigeonManager($this->app);
        $this->app['config']->set('pigeon.default', $default_driver = 'some.driver');

        // act
        $driver = $manager->getDefaultDriver();

        // second act
        unset($this->app['config']['pigeon.default']);
        $null_driver = $manager->getDefaultDriver();

        // assert
        $this->assertEquals($default_driver, $driver);
        $this->assertEquals('null', $null_driver);
    }

    public function test_null_driver_should_throw_exception()
    {
        $this->expectException(NullDriverException::class);
        $manager = new PigeonManager($this->app);
        $this->app['config']->set('pigeon.default', $default_driver = 'null');
        $manager->driver();
    }
}
