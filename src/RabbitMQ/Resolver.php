<?php

namespace Convenia\Pigeon\RabbitMQ;

use Convenia\Pigeon\Contracts\Resolver as ResolverContract;
use PhpAmqpLib\Message\AMQPMessage;

class Resolver implements ResolverContract
{
    /**
     * @var AMQPMessage
     */
    public $message;

    public function __construct(AMQPMessage $message)
    {
        $this->message = $message;
    }

    /**
     * Send a positive acknowledgement signal to the queue and removes the message.
     *
     * @return void
     */
    public function ack(): void
    {
        $this->message->get('channel')
            ->basic_ack($this->message->get('delivery_tag'));
    }

    /**
     * Send a negative acknowledgement signal to the queue and removes the message.
     * If not requeued, it will send the message for the Dead Letter Queue if configured.
     *
     * @return void
     */
    public function reject(bool $requeue = true)
    {
        $this->message->get('channel')
            ->basic_nack($this->message->get('delivery_tag'), false, $requeue);
    }

    /**
     * Get one or more headers from the message.
     *
     * @param  ?string  $key
     *
     * @return mixed
     */
    public function headers(string $key = null): mixed
    {
        return is_null($key)
            ? $this->message->get_properties()
            : $this->message->get($key);
    }
}