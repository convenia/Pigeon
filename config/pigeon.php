<?php

use Convenia\Pigeon\Support\Constants;

return [
    'default'    => env('PIGEON_DRIVER', 'rabbit'),
    'connection' => [
        'credentials' => [
            'user'     => env('PIGEON_USER'),
            'password' => env('PIGEON_PASSWORD'),
        ],
        'host' => [
            'address' => env('PIGEON_ADDRESS'),
            'port'    => env('PIGEON_PORT'),
            'vhost'   => env('PIGEON_VHOST'),
        ],
        'keepalive'     => env('PIGEON_KEEPALIVE', true),
        'heartbeat'     => $heartbeat = env('PIGEON_HEARTBEAT', 10),
        'read_timeout'  => env('PIGEON_READ_TIMEOUT', $heartbeat * 2.5),
        'write_timeout' => env('PIGEON_WRITE_TIMEOUT', $heartbeat * 2.5),
    ],
    'exchange'      => env('PIGEON_EXCHANGE', 'pigeon'),
    'exchange_type' => env('PIGEON_EXCHANGE_TYPE', 'direct'),
    'dead'          => [
        'exchange'    => 'amq.direct',
        'routing_key' => 'dead',
    ],
    'app_name' => env('PIGEON_CONSUMER_TAG', null),
    'consumer' => [
        'tag'           => env('PIGEON_CONSUMER_TAG', null),
        'automatic_ack' => false,
        'on_failure' => 'ack', // 'ack' or 'reject'
    ],
    'queue_declare' => env('PIGEON_DECLARE_QUEUES', true),
    'queue_declare_exists' => Constants::IGNORE_PRECONDITION,
    'headers' => [],
];
