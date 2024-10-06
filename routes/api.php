<?php

use App\Http\Controllers\API\JsonSchemaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomResponseController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'auth'
], function ($router) {
    Route::post('login', [AuthController::class,'login']);
    Route::post('register', [AuthController::class,'register']);
    Route::post('logout', [AuthController::class,'logout']);
    Route::post('refresh', [AuthController::class,'refresh']);
    Route::post('me', [AuthController::class,'me']);
    Route::post('add_roles', [AuthController::class,'addRoles']);
});

Route::apiResource('custom-response', CustomResponseController::class);

Route::get('dashboard', function() {
    return response()->json(['message' => 'Welcome to dashboard'], 200);
});
Route::post('json-schemas', [JsonSchemaController::class, 'createJsonSchema']);
?>

