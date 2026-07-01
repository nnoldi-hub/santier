<?php

return [
    'company_name' => env('EXPORT_COMPANY_NAME', env('APP_NAME', 'Santier')),
    'company_email' => env('EXPORT_COMPANY_EMAIL', env('MAIL_FROM_ADDRESS', 'office@example.com')),
    'company_phone' => env('EXPORT_COMPANY_PHONE', ''),
    'brand_color' => env('EXPORT_BRAND_COLOR', '#f97316'),
    'default_recipients' => array_values(array_filter(array_map('trim', explode(',', (string) env('EXPORT_DEFAULT_RECIPIENTS', ''))))),
];
