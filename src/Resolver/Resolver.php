<?php

namespace Convenia\Pigeon\Resolver;

use PhpAmqpLib\Message\AMQPMessage;

class Resolver implements ResolverContract
{
    protected $message;

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

    public function response(array $data)
    {
        $props = $this->responseProps();
        if ($this->message->has('reply_to')) {
            $queue = $this->message->get('reply_to');
            $msg = new AMQPMessage(json_encode($data), $props);

            $this->message->delivery_info['channel']
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
