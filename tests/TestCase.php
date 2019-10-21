<?php

namespace Convenia\Pigeon\Tests;

use Mockery;
use ArrayAccess;
use Orchestra\Testbench\TestCase as BaseTestCase;
use PHPUnit\Util\InvalidArgumentHelper;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [\Convenia\Pigeon\PigeonServiceProvider::class];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
