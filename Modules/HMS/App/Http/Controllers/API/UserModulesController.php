<?php

namespace Modules\HMS\App\Http\Controllers\API;

use Modules\HMS\App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\HMS\App\Utilities\Helper;
use Illuminate\Support\Facades\Log;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class UserModulesController extends Controller
{
    public static function usersModules(){
    
        try{
            $user = JWTAuth::parseToken()->authenticate();
            $data = Helper::usersModules( $user->id);
            return Helper::successResponse($data,'',200);
         }  
         catch(\Exception $e)
         {
            return Helper::errorResponse($e->getMessage());
         }

    }

}
