<?php

return [
    'default' => 'rabbit',
    'connection' => [
        'credentials' => [
            'user' => env('PIGEON_USER'),
            'password' => env('PIGEON_PASSWORD'),
        ],
        'host' => [
            'address' => env('PIGEON_ADDRESS'),
            'port' => env('PIGEON_PORT'),
            'vhost' => env('PIGEON_VHOST'),
        ],
        'keepalive' => env('PIGEON_KEEPALIVE', true),
        'heartbeat' => env('PIGEON_HEARTBEAT', 10),
        'read_timeout' => env('PIGEON_READ_TIMEOUT', 130),
        'write_timeout' => env('PIGEON_WRITE_TIMEOUT', 130),
    ],
    'exchange' => env('PIGEON_EXCHANGE', 'cPIGEON'),
    'exchange_type' => env('PIGEON_EXCHANGE_TYPE', 'direct'),
    'dead' => [
        'exchange' => 'amq.direct',
        'routing_key' => 'dead'
    ],
    'app_name' => env('PIGEON_CONSUMER_TAG', null),
    'consumer' => [
        'tag' => env('PIGEON_CONSUMER_TAG', null),
        'automatic_ack' => false,
    ]
];
