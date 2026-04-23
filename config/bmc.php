<?php

return [
    'name' => 'BMC',
    'description' => 'Business Model Canvas Module',
    'version' => '1.0.0',

    'routing' => [
        'prefix' => 'bmc',
        'middleware' => ['web', 'auth'],
    ],

    'guard' => 'web',

    'navigation' => [
        'main' => [
            'bmc' => [
                'title' => 'BMC',
                'icon' => 'heroicon-o-squares-2x2',
                'route' => 'bmc.dashboard',
            ],
        ],
    ],

    'sidebar' => [
        'bmc' => [
            'title' => 'Business Model Canvas',
            'icon' => 'heroicon-o-squares-2x2',
            'items' => [
                'dashboard' => [
                    'title' => 'Dashboard',
                    'route' => 'bmc.dashboard',
                    'icon' => 'heroicon-o-home',
                ],
                'canvases' => [
                    'title' => 'Canvases',
                    'route' => 'bmc.canvases.index',
                    'icon' => 'heroicon-o-squares-2x2',
                ],
                'swot' => [
                    'title' => 'SWOT',
                    'route' => 'bmc.swot.index',
                    'icon' => 'heroicon-o-arrow-path-rounded-square',
                ],
            ],
        ],
    ],
    'billables' => [
        [
            'model' => \Platform\Bmc\Models\BmcCanvas::class,
            'type' => 'per_item',
            'label' => 'Business Model Canvas',
            'description' => 'Jedes erstellte BMC verursacht tägliche Kosten nach Nutzung.',
            'pricing' => [
                ['cost_per_day' => 0.005, 'start_date' => '2025-01-01', 'end_date' => null]
            ],
            'free_quota' => null,
            'min_cost' => null,
            'max_cost' => null,
            'billing_period' => 'daily',
            'start_date' => '2026-01-01',
            'end_date' => null,
            'trial_period_days' => 0,
            'discount_percent' => 0,
            'exempt_team_ids' => [],
            'priority' => 100,
            'active' => true,
        ],
    ],
];
