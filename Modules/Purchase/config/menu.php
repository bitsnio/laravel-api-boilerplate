<?php
// stubs/menu.stub
return [
    'module' => [
        'name' => 'Purchase',
        'title' => 'Purchase',
        'icon' => 'fas fa-cube',
        'order' => 1,
        'routes_type' => '',
        'sub_module' => [
            [
                'name' => 'Purchase',
                'title' => 'Purchase',
                'routes_type' => 'full',
                'icon' => 'fas fa-list',
                'middleware' => ['api', 'auth'],
                'order' => 1,
                'actions'=>[
                    [
                    'name' => 'Purchase',
                    'title' => 'Purchase',
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