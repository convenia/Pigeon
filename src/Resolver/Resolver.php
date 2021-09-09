<?php

namespace Convenia\Pigeon\Resolver;

use PhpAmqpLib\Message\AMQPMessage;

class Resolver implements ResolverContract
{
    public $message;

    public function __construct(AMQPMessage $message)
    {
        $this->message = $message;
    }

    public function ack()
    {
        $this->message->delivery_info['channel']
            ->basic_ack($this->message->delivery_info['delivery_tag']);
    }

    public function reject(bool $requeue = true)
    {
        $this->message->delivery_info['channel']
            ->basic_nack($this->message->delivery_info['delivery_tag'], false, $requeue);
    }

    public function headers(string $key = null)
    {
        return is_null($key)
            ? $this->message->get_properties()
            : $this->message->get($key);
    }
}
