<?php

namespace Convenia\Pigeon\Tests\Unit;

use Convenia\Pigeon\PigeonManager;
use Convenia\Pigeon\Tests\TestCase;
use Convenia\Pigeon\Exceptions\Driver\NullDriverException;

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

    public function test_iit_should_add_headers_in_config()
    {
        $manager = new PigeonManager($this->app);

        $this->assertEmpty($this->app['config']->get('pigeon.headers'));

        $manager->headers($headers = [
            'my' => 'header'
        ]);
        $this->assertEquals($headers, $this->app['config']->get('pigeon.headers'));
    }

    public function test_it_should_merge_with_config_headers()
    {
        $manager = new PigeonManager($this->app);

        $default = [
            'foo' => 'bar'
        ];
        $this->app['config']->set('pigeon.headers', $default);
        $manager->headers($headers = [
            'my' => 'header'
        ]);
        $this->assertEquals(array_merge($default, $headers), $this->app['config']->get('pigeon.headers'));
    }

    public function test_it_should_override_config_headers()
    {
        $manager = new PigeonManager($this->app);

        $default = [
            'foo' => 'bar',
            'my' => [
                'deep' => 'header'
            ]
        ];
        $this->app['config']->set('pigeon.headers', $default);
        $manager->headers($headers = [
            'my' => 'header',
            'foo' => 'fighters'
        ]);
        $this->assertEquals($headers, $this->app['config']->get('pigeon.headers'));
    }
}
