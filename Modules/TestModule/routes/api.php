<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::apiResource('add-items', 'AddItemsController');
    Route::apiResource('add-items/update-items', 'AddItems\UpdateItemsController');
});

Route::middleware(['api', 'auth', 'permission'])->group(function () {
    Route::apiResource('add-categories', 'AddCategoriesController');
});

Route::middleware(['api', 'auth'])->group(function () {
    Route::apiResource('add-categories/review', 'AddCategories\ReviewController');
    Route::apiResource('returns', 'ReturnsController');
    Route::apiResource('returns/return-from-customers', 'Returns\ReturnFromCustomersController');
});

