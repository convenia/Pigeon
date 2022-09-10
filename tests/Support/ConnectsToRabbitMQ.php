<?php

namespace Convenia\Pigeon\Tests\Support;

use PhpAmqpLib\Connection\AMQPStreamConnection;

trait ConnectsToRabbitMQ
{
    protected function makeConnection()
    {
        $configs = $this->app['config']['pigeon.connection'];

        return new AMQPStreamConnection(
            data_get($configs, 'host.address'),
            data_get($configs, 'host.port'),
            data_get($configs, 'credentials.user'),
            data_get($configs, 'credentials.password'),
            data_get($configs, 'host.vhost'),
            false,
            'AMQPLAIN',
            null,
            'en_US',
            3.0,
            data_get($configs, 'read_timeout'),
            null,
            data_get($configs, 'keepalive'),
            data_get($configs, 'heartbeat')
        );
    }
}
