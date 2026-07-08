<?php

return [
    'defaults' => [
        'app_name' => env('APP_NAME', 'Modulia'),
        'default_tenant_id' => (int) env('PLATFORM_DEFAULT_TENANT_ID', 1),
        'company_name' => env('PLATFORM_COMPANY_NAME', 'Modulia'),
        'document_issuer_name' => env('PLATFORM_DOCUMENT_ISSUER_NAME', ''),
        'company_phone' => env('PLATFORM_COMPANY_PHONE', ''),
        'company_address' => env('PLATFORM_COMPANY_ADDRESS', ''),
        'support_email' => env('PLATFORM_SUPPORT_EMAIL', 'suport@modulia.ro'),
        'sales_email' => env('PLATFORM_SALES_EMAIL', 'vanzari@modulia.ro'),
        'landing_video_url' => env('PLATFORM_LANDING_VIDEO_URL', ''),
        'social_facebook_url' => env('PLATFORM_SOCIAL_FACEBOOK_URL', ''),
        'social_instagram_url' => env('PLATFORM_SOCIAL_INSTAGRAM_URL', ''),
        'social_linkedin_url' => env('PLATFORM_SOCIAL_LINKEDIN_URL', ''),
        'social_tiktok_url' => env('PLATFORM_SOCIAL_TIKTOK_URL', ''),
        'social_youtube_url' => env('PLATFORM_SOCIAL_YOUTUBE_URL', ''),
        'document_logo_url' => env('PLATFORM_DOCUMENT_LOGO_URL', '/brand/logo_modulia.png'),
        'document_brand_color' => env('PLATFORM_DOCUMENT_BRAND_COLOR', '#FF7A00'),
        'trial_days' => (int) env('PLATFORM_TRIAL_DAYS', 14),
        'public_signup_enabled' => filter_var(env('PLATFORM_PUBLIC_SIGNUP_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'demo_mode_enabled' => filter_var(env('PLATFORM_DEMO_MODE_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    ],
    'admin_emails' => array_values(array_filter(array_map('trim', explode(',', env('PLATFORM_ADMIN_EMAILS', env('DEMO_USER_EMAIL', 'demo@santier.local') . ',iproiect2014@gmail.com'))))),
];