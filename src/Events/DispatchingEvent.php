<?php

namespace Convenia\Pigeon\Events;

use Convenia\Pigeon\Publisher\PublisherContract;

class DispatchingEvent extends BaseEvent
{
    public string $eventName;

    public function __construct(
        PublisherContract $publisher,
        string $eventName,
        array $userData = [],
        array $userMetaData = []
    ) {
        parent::__construct($publisher, $userData, $userMetaData);
        $this->eventName = $eventName;
    }
}
