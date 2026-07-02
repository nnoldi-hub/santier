<?php

return [
    'plans' => [
        'free' => [
            'label' => 'Demo',
            'price' => 0,
            'billing_period' => 'luna',
            'description' => 'Demo standard pentru testare si evaluare rapida.',
            'project_limit' => 1,
            'users_limit' => 1,
            'features' => [
                'gantt' => false,
                'exports_csv' => false,
                'exports_enterprise' => false,
                'document_branding' => false,
            ],
        ],
        'starter' => [
            'label' => 'Brand de baza',
            'price' => 149,
            'billing_period' => 'luna',
            'description' => 'Logo si date de firma in documente, fara customizare avansata.',
            'project_limit' => 5,
            'users_limit' => 3,
            'features' => [
                'gantt' => true,
                'exports_csv' => true,
                'exports_enterprise' => false,
                'document_branding' => true,
                'document_templates' => false,
            ],
        ],
        'pro' => [
            'label' => 'Brand complet',
            'price' => 349,
            'billing_period' => 'luna',
            'description' => 'Brand complet: logo, culori, antet, footer si template-uri de documente.',
            'project_limit' => null,
            'users_limit' => 10,
            'features' => [
                'gantt' => true,
                'exports_csv' => true,
                'exports_enterprise' => true,
                'document_branding' => true,
                'document_templates' => true,
                'document_footer' => true,
            ],
        ],
        'enterprise' => [
            'label' => 'Enterprise',
            'price' => 999,
            'billing_period' => 'luna',
            'description' => 'Mai multe sabloane, aprobari, white-label si domeniu propriu.',
            'project_limit' => null,
            'users_limit' => null,
            'features' => [
                'gantt' => true,
                'exports_csv' => true,
                'exports_enterprise' => true,
                'document_branding' => true,
                'document_templates' => true,
                'document_approvals' => true,
                'white_label' => true,
                'custom_domain' => true,
            ],
        ],
    ],
];
