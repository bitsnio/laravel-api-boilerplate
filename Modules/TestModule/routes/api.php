<?php

use Illuminate\Support\Facades\Route;
use Modules\TestModule\App\Http\Controllers\TestModuleController;
use Modules\TestModule\App\Http\Controllers\MasteritemController;
use Modules\TestModule\App\Http\Controllers\Masteritem\CreateitemController;
use Modules\TestModule\App\Http\Controllers\Masteritem\CreatecategoryController;

Route::apiResources([
    'test-module' => TestModuleController::class,
]);

Route::middleware(['api', 'auth'])->group(function () {
    Route::apiResources([
        'test-module_masteritem' => MasteritemController::class,
        'test-module_masteritem_createitem' => CreateitemController::class,
        'test-module_masteritem_createcategory' => CreatecategoryController::class,
    ]);
});

