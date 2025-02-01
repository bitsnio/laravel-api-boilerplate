<?php

return [
    'module' => [
        'name' => 'TestModule',
        'icon' => 'fas fa-cube',
        'order' => 1,
        'routes_type' => '',
        'sub_module' => [
            [
                'name' => 'TestModule',
                'routes_type' => 'full',
                'icon' => 'fas fa-list',
                'middleware' => [
                    'web',
                    'auth',
                ],
                'order' => 1,
                'actions' => [
                    'name' => 'TestModule',
                    'routes_type' => 'single',
                    'icon' => 'fas fa-list',
                    'middleware' => [
                        'web',
                        'auth',
                    ],
                    'order' => 1,
                ],
            ],
            [
                'name' => 'AddCategories',
                'title' => 'Add Categories',
                'routes_type' => 'full',
                'icon' => 'fas Fa-home',
                'middleware' => [
                    'api',
                    'auth',
                    'permission',
                ],
                'order' => 3,
                'actions' => [
                ],
            ],
            [
                'name' => 'Returns',
                'title' => 'Returns',
                'routes_type' => 'full',
                'icon' => 'fa fa-return',
                'middleware' => [
                    'api',
                    'auth',
                ],
                'order' => 3,
                'actions' => [
                    [
                        'name' => 'ReturnFromCustomers',
                        'title' => 'Return From Customers',
                        'routes_type' => 'full',
                        'icon' => 'Fa fa-cus',
                        'middleware' => [
                            'api',
                            'auth',
                        ],
                        'order' => 1,
                    ],
                ],
            ],
        ],
    ],
];
