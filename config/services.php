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

    'supabase' => [
        'url' => env('SUPABASE_URL'),
        'anon_key' => env('SUPABASE_ANON_KEY'),
        'redirect_urls' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('SUPABASE_REDIRECT_URLS', '')),
        ))),
    ],

    'midtrans' => [
        // Notes: This currently represents one platform Midtrans account.
        // Resolve credentials per payment.vendor_id here if the vendor owns its own account.
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
        'is_sanitized' => true,
        'is_3ds' => true,
    ],

    'locationiq' => [
        'api_key' => env('LOCATIONIQ_API_KEY'),
    ],

    'openrouteservice' => [
        // API Key OpenRouteService untuk perhitungan jarak pengiriman (Directions API).
        'api_key' => env('OPENROUTESERVICE_API_KEY', env('ORS_API_KEY')),
        'key' => env('OPENROUTESERVICE_API_KEY', env('ORS_API_KEY')),
        'base_url' => env('OPENROUTESERVICE_BASE_URL', 'https://api.openrouteservice.org'),
        'profile' => env('OPENROUTESERVICE_PROFILE', 'driving-car'),
        'connect_timeout' => (int) env('OPENROUTESERVICE_CONNECT_TIMEOUT', 2),
        'timeout' => (int) env('OPENROUTESERVICE_TIMEOUT', 4),
        'retry_times' => (int) env('OPENROUTESERVICE_RETRY_TIMES', 1),
        'retry_sleep_ms' => (int) env('OPENROUTESERVICE_RETRY_SLEEP_MS', 150),
        'cache_ttl_minutes' => (int) env('OPENROUTESERVICE_CACHE_TTL', 60),
        // Bila ORS gagal/timeout: pakai jarak garis lurus x faktor ini sebagai perkiraan
        // (hasilnya di-cache singkat). Isi 0 untuk menonaktifkan fallback.
        'fallback_factor' => (float) env('OPENROUTESERVICE_FALLBACK_FACTOR', 1.3),
        'fallback_ttl_seconds' => (int) env('OPENROUTESERVICE_FALLBACK_TTL', 300),
    ],

];
