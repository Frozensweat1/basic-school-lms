<?php

return [
    'default' => env('SMS_CONNECTION', 'log'),
    'default_country_code' => env('SMS_DEFAULT_COUNTRY_CODE', '233'),
    'restrict_to_default_country' => env('SMS_RESTRICT_TO_DEFAULT_COUNTRY', true),
    'sender_id' => env('SMS_SENDER_ID'),
    'queue' => env('SMS_QUEUE', 'sms'),
    'max_recipients' => (int) env('SMS_MAX_RECIPIENTS', 5000),
    'max_segments' => (int) env('SMS_MAX_SEGMENTS', 3),

    'connections' => [
        'log' => [
            'driver' => 'log',
            'channel' => env('SMS_LOG_CHANNEL', 'stack'),
        ],

        'http' => [
            'driver' => 'http',
            'endpoint' => env('SMS_HTTP_ENDPOINT'),
            'token' => env('SMS_HTTP_TOKEN'),
            'token_header' => env('SMS_HTTP_TOKEN_HEADER', 'Authorization'),
            'token_prefix' => env('SMS_HTTP_TOKEN_PREFIX', 'Bearer'),
            'recipient_field' => env('SMS_HTTP_RECIPIENT_FIELD', 'to'),
            'message_field' => env('SMS_HTTP_MESSAGE_FIELD', 'message'),
            'sender_field' => env('SMS_HTTP_SENDER_FIELD', 'sender'),
            'message_id_path' => env('SMS_HTTP_MESSAGE_ID_PATH', 'message_id'),
            'status_path' => env('SMS_HTTP_STATUS_PATH', 'status'),
            'idempotency_header' => env('SMS_HTTP_IDEMPOTENCY_HEADER', 'Idempotency-Key'),
            'connect_timeout' => (int) env('SMS_HTTP_CONNECT_TIMEOUT', 5),
            'timeout' => (int) env('SMS_HTTP_TIMEOUT', 15),
            'verify_tls' => env('SMS_HTTP_VERIFY_TLS', true),
            'extra_payload' => [],
        ],
    ],
];
