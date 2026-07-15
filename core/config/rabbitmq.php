<?php

return [

    /*
    |--------------------------------------------------------------------------
    | RabbitMQ Connection Configuration
    |--------------------------------------------------------------------------
    |
    | These values are used to connect to the RabbitMQ broker.
    | All values MUST be set via environment variables.
    | There are no insecure defaults; the application will fail fast if any
    | required variable is missing.
    |
    */

    'host' => env('RABBITMQ_HOST'),
    'port' => env('RABBITMQ_PORT', 5672),
    'user' => env('RABBITMQ_USER'),
    'password' => env('RABBITMQ_PASSWORD'),
    'vhost' => env('RABBITMQ_VHOST', '/'),

    'queues' => [
        'transaction' => env('RABBITMQ_TRANSACTION_QUEUE', 'transaction_queue'),
    ],

];
