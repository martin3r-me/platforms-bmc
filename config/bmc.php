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
            ],
        ],
    ],
];
