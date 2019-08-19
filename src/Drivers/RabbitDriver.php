<?php

namespace Convenia\Pigeon\Drivers;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitDriver extends Driver
{
    public function getConnection()
    {
        if (! $this->connection) {
            $this->connection = $this->makeConnection();
        }
        if ($this->connection->isConnected()) {
            return $this->connection;
        }

        $this->connection->reconnect();

        return $this->connection;
    }

    public function getChannel(): AMQPChannel
    {
        return $this->getConnection()->channel(1);
    }

    public function makeConnection()
    {
        return new AMQPStreamConnection(
            $host = $this->app['config']['pigeon.connection.host.address'],
            $port = $this->app['config']['pigeon.connection.host.port'],
            $user = $this->app['config']['pigeon.connection.credentials.user'],
            $password = $this->app['config']['pigeon.connection.credentials.password'],
            $vhost = $this->app['config']['pigeon.connection.host.vhost'],
            $insist = false,
            $login_method = 'AMQPLAIN',
            $login_response = null,
            $locale = 'en_US',
            $connection_timeout = 3.0,
            $read_write_timeout = (int) $this->app['config']['pigeon.connection.read_timeout'],
            $context = null,
            $keepalive = (bool) $this->app['config']['pigeon.connection.keepalive'],
            $heartbeat = (int) $this->app['config']['pigeon.connection.heartbeat']
        );
    }
}
