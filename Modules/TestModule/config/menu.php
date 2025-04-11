<?php
// stubs/menu.stub
return [
    'module' => [
        'name' => 'TestModule',
        'title' => 'TestModule',
        'icon' => 'fas fa-cube',
        'order' => 1,
        'routes_type' => '',
        'sub_module' => [
            [
                'name' => 'masteritem',
                'title' => 'Master Item',
                'routes_type' => 'full',
                'icon' => 'fas fa-list',
                'middleware' => ['api', 'auth'],
                'order' => 1,
                'actions' => [
                    [
                        'name' => 'createitem',
                        'title' => 'Create Item',
                        'routes_type' => 'single',
                        'icon' => 'fas fa-list',
                        'middleware' => ['api', 'auth'],
                        'order' => 1
                    ],
                    [
                        'name' => 'createcategory',
                        'title' => 'Create Category',
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
