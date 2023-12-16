<?php

namespace Convenia\Pigeon\Publisher;

use Convenia\Pigeon\Events\MessagePublished;
use Convenia\Pigeon\Events\PublishingMessage;

class Publisher implements PublisherContract
{
    use PublisherConcern;

    public bool $disableEvents = false;

    public function bind(string $queue): PublisherContract
    {
        $this->driver->getChannel()->queue_bind($queue, $this->exchange, $this->routing);

        return $this;
    }

    public function publish(array $message, array $properties = [], int $channelId = null)
    {
        if (!$this->disableEvents) {
            PublishingMessage::dispatch(
                $this,
                $message,
                $properties
            );
        }

        $msg = $this->makeMessage($message, $properties);
        $this->driver->getChannel($channelId)->basic_publish(
            $msg,
            $this->exchange,
            $this->routing
        );

        if (!$this->disableEvents) {
            MessagePublished::dispatch(
                $this,
                $message,
                $properties
            );
        }
    }
}
