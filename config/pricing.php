<?php

return [
    'plans' => [
        'free' => [
            'label' => 'Free',
            'project_limit' => 1,
            'features' => [
                'gantt' => false,
                'exports_csv' => false,
                'exports_enterprise' => false,
            ],
        ],
        'starter' => [
            'label' => 'Starter',
            'project_limit' => 5,
            'features' => [
                'gantt' => true,
                'exports_csv' => true,
                'exports_enterprise' => false,
            ],
        ],
        'pro' => [
            'label' => 'Pro',
            'project_limit' => null,
            'features' => [
                'gantt' => true,
                'exports_csv' => true,
                'exports_enterprise' => true,
            ],
        ],
        'enterprise' => [
            'label' => 'Enterprise',
            'project_limit' => null,
            'features' => [
                'gantt' => true,
                'exports_csv' => true,
                'exports_enterprise' => true,
            ],
        ],
    ],
];
