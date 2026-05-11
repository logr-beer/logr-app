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

    'catalog_beer' => [
        'key' => env('CATALOG_BEER_API_KEY'),
    ],

    'logr_db' => [
        'url' => env('LOGR_DB_URL'),
    ],

    'logr' => [
        'discord_bot_url' => env('LOGR_DISCORD_BOT_URL', 'https://discord.logr.beer'),
    ],

    'untappd' => [
        'api_key' => env('UNTAPPD_API_KEY'),
        'api_secret' => env('UNTAPPD_API_SECRET'),
    ],

];
