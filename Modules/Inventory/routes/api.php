<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\App\Http\Controllers\InventoryController;
use Modules\Inventory\App\Http\Controllers\MasterItemController;
use Modules\Inventory\App\Http\Controllers\MasterItem\UomController;
use Modules\Inventory\App\Http\Controllers\MasterItem\CategoriesController;
use Modules\Inventory\App\Http\Controllers\WarehousesController;
use Modules\Inventory\App\Http\Controllers\Warehouses\AdjustmentsController;

Route::apiResources([
    'inventory' => InventoryController::class,
]);

Route::middleware(['api', 'auth'])->group(function () {
    Route::apiResources([
        'inventory_master_item' => MasterItemController::class,
        'inventory_master_item_uom' => UomController::class,
        'inventory_master_item_categories' => CategoriesController::class,
        'inventory_warehouses' => WarehousesController::class,
        'inventory_warehouses_adjustments' => AdjustmentsController::class,
    ]);
});

