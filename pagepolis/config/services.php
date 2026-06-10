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
        'key'        => env('ANTHROPIC_API_KEY'),
        // Modelo para usuarios de pago (máxima calidad).
        'model'      => env('ANTHROPIC_MODEL', 'claude-opus-4-8'),
        // Modelo para usuarios gratis (excelente calidad, mucho más barato).
        'model_free' => env('ANTHROPIC_MODEL_FREE', 'claude-sonnet-4-6'),

        // GUARDIÁN DE PRESUPUESTO: topes de gasto de IA. Al alcanzarlos, las
        // generaciones se pausan y nunca se gasta de más sin permiso explícito
        // (subir estos valores). Protege frente a abuso por bots o a mano.
        // 0 = sin tope. El diario, si se deja vacío, se deriva del mensual/10.
        'monthly_budget_usd' => (float) env('AI_MONTHLY_BUDGET_USD', 50),
        'daily_budget_usd'   => env('AI_DAILY_BUDGET_USD'),

        // Precios por millón de tokens (USD) para estimar el coste mensual.
        // Ajusta si Anthropic cambia su tarifa. La clave es un prefijo del modelo.
        'pricing' => [
            'claude-opus'   => ['in' => 15.0, 'out' => 75.0],
            'claude-sonnet' => ['in' => 3.0,  'out' => 15.0],
            'claude-haiku'  => ['in' => 0.8,  'out' => 4.0],
            'default'       => ['in' => 15.0, 'out' => 75.0],
        ],
    ],

    'stripe' => [
        'key'             => env('STRIPE_KEY'),
        'secret'          => env('STRIPE_SECRET'),
        'webhook_secret'  => env('STRIPE_WEBHOOK_SECRET'),
        'price_monthly'   => env('STRIPE_PRICE_MONTHLY'),
        'price_yearly'    => env('STRIPE_PRICE_YEARLY'),
    ],

    'dinahosting' => [
        'user' => env('DINAHOSTING_USER'),
        'pass' => env('DINAHOSTING_PASS'),
    ],

    'whatsapp' => [
        'token'        => env('WHATSAPP_TOKEN'),         // Token de acceso de Meta
        'phone_id'     => env('WHATSAPP_PHONE_ID'),      // ID del número de WhatsApp Business
        'app_secret'   => env('WHATSAPP_APP_SECRET'),    // App Secret para verificar firmas
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),  // Token de verificación del webhook
    ],

    'cloudflare' => [
        'token'   => env('CLOUDFLARE_API_TOKEN'),
        'zone_id' => env('CLOUDFLARE_ZONE_ID'),
    ],

    'server' => [
        'public_ip' => env('SERVER_PUBLIC_IP'),
    ],

];
