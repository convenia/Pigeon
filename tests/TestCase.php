<?php

namespace Convenia\AMQP\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [\Convenia\AMQP\AMQPServiceProvider::class];
    }
}
