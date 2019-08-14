<?php

namespace Convenia\Pigeon\Tests;

use Mockery;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [\Convenia\Pigeon\PigeonServiceProvider::class];
    }

    protected function tearDown()
    {
        parent::tearDown();
        Mockery::close();
    }
}
