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

    'whatsapp' => [
        'enabled' => env('WHATSAPP_ENABLED', false),
        'graph_version' => env('WHATSAPP_GRAPH_VERSION', 'v23.0'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'token' => env('WHATSAPP_ACCESS_TOKEN'),
        'schedule_published_template' => env('WHATSAPP_SCHEDULE_PUBLISHED_TEMPLATE'),
        'swap_pending_template' => env('WHATSAPP_SWAP_PENDING_TEMPLATE', 'troca_pendente'),
        'language' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'pt_BR'),
    ],

    'notification_copy' => [
        'enabled' => env('NOTIFICATION_COPY_ENABLED', false),
        'name' => env('NOTIFICATION_COPY_NAME'),
        'email' => env('NOTIFICATION_COPY_EMAIL'),
        'phone' => env('NOTIFICATION_COPY_PHONE'),
    ],

    'notification_test' => [
        'enabled' => env('NOTIFICATION_TEST_MODE', false),
        'recipient_name' => env('NOTIFICATION_TEST_RECIPIENT_NAME', 'Teste DoctorTurn'),
        'emails' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('NOTIFICATION_TEST_EMAILS', '')),
        ))),
        'phones' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('NOTIFICATION_TEST_PHONES', '')),
        ))),
    ],

];
