<?php

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

    'backend' => [
        'url' => rtrim((string) env('BACKEND_API_URL', env('VITE_API_BASE', 'http://127.0.0.1:8000')), '/'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
        'api_key' => env('GOOGLE_DRIVE_API_KEY'),
        'app_id' => env('GOOGLE_DRIVE_APP_ID'),
        'refresh_token' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
        'owner_managed' => filter_var(env('GOOGLE_DRIVE_OWNER_MANAGED', false), FILTER_VALIDATE_BOOL),
    ],

];
