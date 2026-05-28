<?php

return [

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'anthropic' => [
        'key'   => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-20250514'),
    ],

    'stripe' => [
        'key'             => env('STRIPE_KEY'),
        'secret'          => env('STRIPE_SECRET'),
        'webhook_secret'  => env('STRIPE_WEBHOOK_SECRET'),
        'price_monthly'   => env('STRIPE_PRICE_MONTHLY'),
        'price_yearly'    => env('STRIPE_PRICE_YEARLY'),
    ],

    'namecheap' => [
        'api_user'  => env('NAMECHEAP_API_USER'),
        'api_key'   => env('NAMECHEAP_API_KEY'),
        'client_ip' => env('NAMECHEAP_CLIENT_IP'),
        'sandbox'   => env('NAMECHEAP_SANDBOX', false),
    ],

    'cloudflare' => [
        'token'   => env('CLOUDFLARE_API_TOKEN'),
        'zone_id' => env('CLOUDFLARE_ZONE_ID'),
    ],

    'server' => [
        'public_ip' => env('SERVER_PUBLIC_IP'),
    ],

];
