<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Domain events
    |--------------------------------------------------------------------------
    |
    | See App\Services\EventPublisher. "log" needs no infrastructure; "kafka"
    | targets Apache Kafka / Amazon MSK via the rdkafka PHP extension.
    |
    */

    'events' => [
        'driver' => env('EVENT_DRIVER', 'log'),
        'topic_prefix' => env('EVENT_TOPIC_PREFIX', ''),
        'kafka' => [
            'brokers' => env('KAFKA_BROKERS'),
            'security_protocol' => env('KAFKA_SECURITY_PROTOCOL', 'PLAINTEXT'),
            'sasl_mechanism' => env('KAFKA_SASL_MECHANISM'),
            'sasl_username' => env('KAFKA_SASL_USERNAME'),
            'sasl_password' => env('KAFKA_SASL_PASSWORD'),
            'flush_timeout_ms' => (int) env('KAFKA_FLUSH_TIMEOUT_MS', 2000),
        ],
    ],

    'onboarding' => [
        'skip_allowed' => (bool) env('ONBOARDING_SKIP_ALLOWED', true),
    ],

    'personalization' => [
        'cache_hours' => (int) env('RECOMMENDATION_CACHE_HOURS', 6),
    ],

];
