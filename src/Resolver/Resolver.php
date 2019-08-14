<?php

namespace Convenia\Pigeon\Resolver;

use Convenia\Pigeon\Drivers\DriverContract;
use PhpAmqpLib\Message\AMQPMessage;

class Resolver implements ResolverContract
{
    protected $driver;

    protected $message;

    public function __construct(DriverContract $driver, AMQPMessage $message)
    {
        $this->driver = $driver;
        $this->message = $message;
    }

    public function ack()
    {
        $this->driver
            ->getChannel()
            ->basic_ack($this->message->delivery_info['delivery_tag']);
    }

    public function reject(bool $requeue = true)
    {
        $this->driver
            ->getChannel()
            ->basic_reject($this->message->delivery_info['delivery_tag'], $requeue);
    }

    public function response(array $data)
    {
        $props = $this->responseProps();
        if ($this->message->has('reply_to')) {
            $queue = $this->message->get('reply_to');
            $msg = new AMQPMessage(json_encode($data), $props);

            $this->driver
                ->getChannel()
                ->basic_publish($msg, '', $queue);
        }

        $this->ack();
    }

    private function responseProps()
    {
        $message_props = [
            'correlation_id' => $this->message->has('correlation_id') ? $this->message->get('correlation_id') : null,
        ];

        return $message_props;
    }
}
