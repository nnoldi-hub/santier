<?php

return [
    'defaults' => [
        'app_name' => env('APP_NAME', 'Santier'),
        'default_tenant_id' => (int) env('PLATFORM_DEFAULT_TENANT_ID', 1),
        'company_name' => env('PLATFORM_COMPANY_NAME', 'Santier'),
        'document_issuer_name' => env('PLATFORM_DOCUMENT_ISSUER_NAME', ''),
        'company_phone' => env('PLATFORM_COMPANY_PHONE', ''),
        'company_address' => env('PLATFORM_COMPANY_ADDRESS', ''),
        'support_email' => env('PLATFORM_SUPPORT_EMAIL', 'support@santier.ro'),
        'sales_email' => env('PLATFORM_SALES_EMAIL', 'sales@santier.ro'),
        'document_logo_url' => env('PLATFORM_DOCUMENT_LOGO_URL', ''),
        'document_brand_color' => env('PLATFORM_DOCUMENT_BRAND_COLOR', '#f97316'),
        'trial_days' => (int) env('PLATFORM_TRIAL_DAYS', 14),
        'public_signup_enabled' => filter_var(env('PLATFORM_PUBLIC_SIGNUP_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'demo_mode_enabled' => filter_var(env('PLATFORM_DEMO_MODE_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    ],
    'admin_emails' => array_values(array_filter(array_map('trim', explode(',', env('PLATFORM_ADMIN_EMAILS', env('DEMO_USER_EMAIL', 'demo@santier.local') . ',iproiect2014@gmail.com'))))),
];