<?php

namespace Convenia\Pigeon\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Convenia\Pigeon\Publisher\PublisherContract;

abstract class BaseMessageEvent
{
    use Dispatchable;

    public PublisherContract $publisher;
    public array $userData = [];
    public array $userMetaData = [];

    public function __construct(
        PublisherContract $publisher,
        array $userData = [],
        array $userMetaData = []
    ) {
        $this->publisher = $publisher;
        $this->userData = $userData;
        $this->userMetaData = $userMetaData;
    }
}
