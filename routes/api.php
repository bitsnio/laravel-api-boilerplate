<?php

use App\Http\Controllers\API\JsonSchemaController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AutocompleteController;

use App\Traits\JsonResponse;

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

Route::group(['prefix'=>'common'],function () {
    Route::post('autocomplete',[AutocompleteController::class,'index']);
    Route::get('json',function (){
        return JsonResponse::successResponse(json_decode('{
            "CrisisDefinition": [
              {
                "id": 1,
                "title": "Physical disaster",
                "description": "Some description of the model",
                "avatar": "logo-ionic",
                "class": "crisis",
                "isChecked": false,
                "color": "success",
                "subHeader": "Select type of crisis",
                "popoverData": []
              },
              {
                "id": 2,
                "title": "Cyber",
                "description": "Some description of the model",
                "avatar": "logo-ionic",
                "class": "crisis",
                "isChecked": false,
                "color": "warning",
                "subHeader": "Select type of crisis",
                "popoverData": []
              }
            ],
            "Department": [
              {
                "id": 1,
                "title": "IT",
                "description": null,
                "avatar": "logo-ionic",
                "class": "departments",
                "isChecked": false,
                "color": "danger",
                "subHeader": "Select Impacted Department",
                "popoverData": []
              },
              {
                "id": 2,
                "title": "finance",
                "description": null,
                "avatar": "logo-ionic",
                "class": "departments",
                "isChecked": false,
                "color": "warning",
                "subHeader": "Select Impacted Department",
                "popoverData": []
              }
            ],
            "ImpactedComponent": [
              {
                "id": 1,
                "title": "c1",
                "description": "Some description of the model",
                "avatar": "logo-ionic",
                "class": "components",
                "isChecked": false,
                "color": "warning",
                "subHeader": "Select Your profile",
                "popoverData": []
              },
              {
                "id": 3,
                "title": "cp3",
                "description": "Some description of the model",
                "avatar": "logo-ionic",
                "class": "components",
                "isChecked": false,
                "color": "dark",
                "subHeader": "Select Your profile",
                "popoverData": [
                  {
                    "title": "c1",
                    "id": 1,
                    "isChecked": false
                  },
                  {
                    "title": "c2",
                    "id": 2,
                    "isChecked": false
                  },
                  {
                    "title": "c3",
                    "id": 3,
                    "isChecked": false
                  }
                ]
              }
            ],
            "User": [
              {
                "id": 2,
                "title": "Admin",
                "avatar": "logo-ionic",
                "color": "warning",
                "class": "profile",
                "isChecked": false,
                "subHeader": "Select Your profile",
                "popoverData": []
              }
            ]
          }'));
    });
});


Route::get('dashboard', function() {
    return response()->json(['message' => 'Welcome to dashboard'], 200);
});
Route::post('json-schemas', [JsonSchemaController::class, 'createJsonSchema']);


// function successResponse($data, $message = null, $code = 200,$token=null){
//     return response()->json([
//         'success'=> true, 
//         'message' => $message, 
//         'data' => $data,
//         'token'=>$token
//     ], $code);
// }
?>