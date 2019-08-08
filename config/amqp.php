<?php

return [
    'connection' => [
        'credentials' => [
            'user' => env('AMQP_USER'),
            'password' => env('AMQP_PASSWORD'),
        ],
        'host' => [
            'address' => env('AMQP_ADDRESS'),
            'port' => env('AMQP_PORT'),
            'vhost' => env('AMQP_VHOST'),
        ],
        'keepalive' => env('AMQP_KEEPALIVE', true),
        'heartbeat' => env('AMQP_HEARTBEAT', 10),
        'read_timeout' => env('AMQP_READ_TIMEOUT', 130),
        'write_timeout' => env('AMQP_WRITE_TIMEOUT', 130),
    ],
    'exchange' => env('AMQP_EXCHANGE', 'amq.direct'),
    'exchange_type' => env('AMQP_EXCHANGE_TYPE', 'direct'),
    'consumer' => [
        'tag' => env('AMQP_CONSUMER_TAG', null),
        'automatic_ack' => false,
    ],
];
