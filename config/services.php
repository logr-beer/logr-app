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

    'catalog_beer' => [
        'key' => env('CATALOG_BEER_API_KEY'),
    ],

    'logr_db' => [
        'url' => env('LOGR_DB_URL'),
    ],

    'logr' => [
        'discord_url' => env('LOGR_DISCORD_URL', 'https://discord.logr.beer'),
    ],

    'untappd' => [
        'username' => env('UNTAPPD_USERNAME'),
        'rss_feeds' => env('UNTAPPD_RSS_FEEDS'),
        'api_key' => env('UNTAPPD_API_KEY'),
        'api_secret' => env('UNTAPPD_API_SECRET'),
    ],

    'discord' => [
        'webhooks' => env('DISCORD_WEBHOOKS'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
