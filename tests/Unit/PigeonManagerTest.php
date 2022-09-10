<?php

namespace Convenia\Pigeon\Tests\Unit;

use Convenia\Pigeon\Drivers\RabbitDriver;
use Convenia\Pigeon\PigeonManager;
use Convenia\Pigeon\Tests\TestCase;
use Error;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Mockery\MockInterface;

class PigeonManagerTest extends TestCase
{
    public function test_it_should_return_default_driver_based_on_config_or_null()
    {
        $manager = $this->app->make('pigeon');
        $this->app['config']->set('pigeon.default', $default_driver = 'some.driver');

        $driver = $manager->getDefaultDriver();
        $this->assertEquals($default_driver, $driver);

        unset($this->app['config']['pigeon.default']);
        $null_driver = $manager->getDefaultDriver();

        $this->assertEquals(null, $null_driver);
    }

    public function test_null_driver_should_throw_exception()
    {
        $wrongDriver = 'some driver';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Driver [' . $wrongDriver . '] not supported.');

        $manager = new PigeonManager($this->app);

        $this->app['config']->set('pigeon.default', $wrongDriver);

        $manager->driver();
    }

    public function test_iit_should_add_headers_in_config()
    {
        $manager = new PigeonManager($this->app);

        $this->assertEmpty($this->app['config']->get('pigeon.headers'));

        $manager->headers($headers = [
            'my' => 'header',
        ]);
        $this->assertEquals($headers, $this->app['config']->get('pigeon.headers'));
    }

    public function test_it_should_merge_with_config_headers()
    {
        $manager = new PigeonManager($this->app);

        $default = [
            'foo' => 'bar',
        ];
        $this->app['config']->set('pigeon.headers', $default);
        $manager->headers($headers = [
            'my' => 'header',
        ]);
        $this->assertEquals(array_merge($default, $headers), $this->app['config']->get('pigeon.headers'));
    }

    public function test_it_should_override_config_headers()
    {
        $manager = new PigeonManager($this->app);

        $default = [
            'foo' => 'bar',
            'my' => [
                'deep' => 'header',
            ],
        ];
        $this->app['config']->set('pigeon.headers', $default);
        $manager->headers($headers = [
            'my' => 'header',
            'foo' => 'fighters',
        ]);
        $this->assertEquals($headers, $this->app['config']->get('pigeon.headers'));
    }

    public function test_it_should_delegate_calls_to_driver()
    {
        Config::set('pigeon.default', 'mock');

        $driver = $this->mock(RabbitDriver::class, function (MockInterface $mock) {
            $mock->shouldReceive('getChannel')
                ->once()
                ->with(28);
        });

        $manager = $this->app->make('pigeon');
        $manager->extend('mock', function ($app) use ($driver) {
            return $driver;
        });

        $manager->getChannel(28);

        $this->assertFalse(method_exists($manager, 'getChannel'));
    }

    public function test_it_should_throw_exception_delegating_unimplemented_method()
    {
        $this->expectException(Error::class);
        $this->expectExceptionMessage('Call to undefined method Convenia\Pigeon\Drivers\RabbitDriver::someNonExistingMethod()');

        $manager = new PigeonManager($this->app);
        $manager->someNonExistingMethod();
    }
}
