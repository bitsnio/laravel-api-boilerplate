<?php

return [
    'module' => [
        'name' => 'TestModule',
        'icon' => 'fas fa-cube',
        'order' => 1,
        'routes_type' => '',
        'sub_module' => [
            [
                'name' => 'AddItems',
                'routes_type' => 'full',
                'icon' => 'fas fa-list',
                'middleware' => ['web', 'auth'],
                'order' => 1,
                'actions' => [
                    [
                        'name' => 'UpdateItems',
                        'routes_type' => 'single',
                        'icon' => 'fas fa-list',
                        'middleware' => ['web', 'auth'],
                        'order' => 1,
                    ]
                ],
            ],
            [
                'name' => 'AddCategories',
                'title' => 'Add Categories',
                'routes_type' => 'full',
                'icon' => 'fas Fa-home',
                'middleware' => ['api', 'auth', 'permission'],
                'order' => 3,
                'actions' => [
                    [
                        'name' => 'Review',
                        'title' => 'Review',
                        'routes_type' => 'full',
                        'icon' => 'fas fa-list',
                        'middleware' => ['api', 'auth'],
                        'order' => 2,
                    ],
                ],
            ],
            [
                'name' => 'Returns',
                'title' => 'Returns',
                'routes_type' => 'full',
                'icon' => 'fa fa-return',
                'middleware' => ['api', 'auth'],
                'order' => 3,
                'actions' => [
                    [
                        'name' => 'ReturnFromCustomers',
                        'title' => 'Return From Customers',
                        'routes_type' => 'full',
                        'icon' => 'Fa fa-cus',
                        'middleware' => ['api', 'auth'],
                        'order' => 1,
                    ],
                ],
            ],
        ],
    ],
];
