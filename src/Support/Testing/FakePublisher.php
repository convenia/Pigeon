<?php

namespace Convenia\Pigeon\Support\Testing;

use Convenia\Pigeon\Publisher\PublisherConcern;
use Convenia\Pigeon\Publisher\PublisherContract;

class FakePublisher implements PublisherContract
{
    use PublisherConcern;

    public function bind(string $queue): PublisherContract
    {
        throw new \Exception('Pigeon Fake does not support binding');
    }

    public function publish(array $message, array $properties = [], int $channelId = null)
    {
        throw new \Exception('Pigeon Fake does not support publishing');
    }
}