<?php
// stubs/menu.stub
return [
    'module' => [
        'name' => 'Inventory',
        'title' => 'Inventory',
        'icon' => 'fas fa-cube',
        'order' => 1,
        'routes_type' => '',
        'sub_module' => [
            [
                'name' => 'CreateItem',
                'title' => 'Create Item',
                'routes_type' => 'full',
                'icon' => 'fas fa-list',
                'middleware' => ['api', 'auth'],
                'order' => 1,
                'actions'=>[
                    [
                    'name' => 'CreateCategory',
                    'title' => 'CreateCategory',
                    'routes_type' => 'single',
                    'icon' => 'fas fa-list',
                    'middleware' => ['api', 'auth'],
                    'order' => 1
                    ]
                ]
            ]
        ]
    ]
];