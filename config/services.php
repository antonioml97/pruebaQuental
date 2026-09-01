<?php

declare(strict_types=1);

/**
 * Configura las integraciones externas utilizadas por la aplicación.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
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

    'rick_and_morty' => [
        'url' => env('RICK_AND_MORTY_API_URL', 'https://rickandmortyapi.com/api'),
        'timeout' => (int) env('RICK_AND_MORTY_TIMEOUT', 10),
        'connect_timeout' => (int) env('RICK_AND_MORTY_CONNECT_TIMEOUT', 5),
        'retry_times' => (int) env('RICK_AND_MORTY_RETRY_TIMES', 3),
        'retry_sleep_milliseconds' => (int) env('RICK_AND_MORTY_RETRY_SLEEP_MILLISECONDS', 100),
    ],

];
