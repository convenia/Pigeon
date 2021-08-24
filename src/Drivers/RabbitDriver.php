<?php

namespace Convenia\Pigeon\Drivers;

use Convenia\Pigeon\Support\Constants;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPHeartbeatMissedException;

class RabbitDriver extends Driver
{
    public function getConnection()
    {
        if (! $this->connection) {
            $this->connection = $this->makeConnection();
        }

        if (! $this->connection->isConnected() || $this->missedHeartBeat()) {
            $this->connection->reconnect();
        }

        return $this->connection;
    }

    public function getChannel(int $id = null): AMQPChannel
    {
        return $this->getConnection()->channel($id);
    }

    public function queueDeclare(string $name, array $properties)
    {
        try {
            $this->getChannel()->queue_declare($name, false, true, false, false, false, $this->getProps($properties));
        } catch (Exception $e) {
            Str::contains($e->getMessage(), 'PRECONDITION') ?: $this->handleQueuePrecondition($e, $name, $properties);
        }
    }

    protected function handleQueuePrecondition(Exception $e, string $name, array $properties)
    {
        switch ($this->app['config']['pigeon.queue_declare_exists']) {
            case Constants::IGNORE_PRECONDITION:
                return null;
            case Constants::REPLACE_ON_PRECONDITION:
                Log::critical('Handling declare precondition with: Constants::REPLACE_ON_PRECONDITION');
                $this->getChannel()->queue_delete($name);
                $this->queueDeclare($name, $properties);

                return null;
        }

        throw $e;
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

    public function quitHard()
    {
        $this->getConnection()->close();
    }

    public function quit()
    {
        $this->quitHard();
    }

    protected function missedHeartBeat(): bool
    {
        try {
            $this->connection->checkHeartBeat();
        } catch (AMQPHeartbeatMissedException $exception) {
            return true;
        }

        return false;
    }
}
