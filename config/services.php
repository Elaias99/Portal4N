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

    'internal' => [
        'key' => env('INTERNAL_TRACKING_KEY'),
    ],


    'microsoft' => [
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'tenant_id' => env('MICROSOFT_TENANT_ID'),
    ],

    'onedrive' => [
        'enabled' => env('ONEDRIVE_ENABLED', true),

        'tenant_id' => env('MICROSOFT_ONEDRIVE_TENANT_ID'),
        'client_id' => env('MICROSOFT_ONEDRIVE_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_ONEDRIVE_CLIENT_SECRET'),
        'drive_id' => env('MICROSOFT_ONEDRIVE_DRIVE_ID'),
        'folder_id' => env('MICROSOFT_ONEDRIVE_FOLDER_ID'),
    ],

    'tracking' => [
        'base_url' => env('TRACKING_API_BASE_URL', 'https://admin.4nortes.app/api/v1/tracking'),
        'token' => env('TRACKING_API_TOKEN'),
        'timeout' => env('TRACKING_API_TIMEOUT', 15),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ors' => [
        'key' => env('ORS_API_KEY'),
    ],

];