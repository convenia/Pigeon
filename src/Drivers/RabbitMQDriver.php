<?php

namespace Convenia\Pigeon\Drivers;

use Convenia\Pigeon\Support\Constants;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPHeartbeatMissedException;

class RabbitMQDriver extends Driver
{
    /**
     * Gets the connection object of the class.
     * Tries to reconnect if the connectin it's lost.
     *
     * @return \PhpAmqpLib\Connection\AMQPStreamConnection
     */
    public function connection()
    {
        if (! $this->connection->isConnected() || $this->missedHeartBeat()) {
            $this->connection->reconnect();
        }

        return $this->connection;
    }

    public function getChannel(int $id = null): AMQPChannel
    {
        return $this->connection()->channel($id);
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

    /**
     * Closes the connection disgracefully.
     *
     * @return void
     */
    public function quitHard(): void
    {
        $this->connection()->close();
    }

    /**
     * Closes the connection gracefully.
     *
     * @return void
     */
    public function quit(): void
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
