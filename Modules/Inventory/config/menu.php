<?php

return [
    'module' => [
        'name' => 'Inventory',
        'title' => 'Inventory',
        'icon' => 'fas fa-cube',
        'order' => 1,
        'routes_type' => '',
        'sub_module' => [
            [
                'name' => 'master_item',
                'title' => 'Master Item',
                'routes_type' => 'full',
                'icon' => 'fas fa-list',
                'middleware' => ['api', 'auth'],
                'order' => 1,
                'actions' => [
                    [
                        'name' => 'uom',
                        'title' => 'Unit Of Measures',
                        'routes_type' => 'single',
                        'icon' => 'fas fa-list',
                        'middleware' => ['api', 'auth'],
                        'order' => 1,
                    ],
                    [
                        'name' => 'categories',
                        'title' => 'Item Categories',
                        'routes_type' => 'single',
                        'icon' => 'fas fa-list',
                        'middleware' => ['api', 'auth'],
                        'order' => 2,
                    ]
                ],
            ],
            [
                'name' => 'warehouses',
                'title' => 'Multi warehouses',
                'routes_type' => 'full',
                'icon' => 'fas fa-list',
                'middleware' => ['api', 'auth'],
                'order' => 2,
                'actions' => [
                    [
                        'name' => 'Adjustments',
                        'title' => 'Inventory Adjustments',
                        'routes_type' => 'full',
                        'icon' => 'fas fa-list',
                        'middleware' => ['api', 'auth'],
                        'order' => 1,
                    ],
                ],
            ],
        ],
    ],
];
