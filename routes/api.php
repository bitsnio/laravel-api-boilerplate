<?php

use App\Http\Controllers\API\JsonSchemaController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\JwtAuthMiddleware;

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


Route::get('dashboard', function() {
    return response()->json(['message' => 'Welcome to dashboard'], 200);
});
Route::post('jsons', [JsonSchemaController::class, 'createJsonSchema']);
?>