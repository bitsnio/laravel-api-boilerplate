<?php

namespace Modules\HMS\App\Http\Controllers\API;

use Modules\HMS\App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\HMS\App\Utilities\Helper;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AssignSubModuleController extends Controller
{
    public static function getSubModules(){
    
        try{
            // dd("yes");
            $user = JWTAuth::parseToken()->authenticate();
            $data = self::assignModules( $user->id);
            // dd($data);
            return Helper::successResponse($data,'',200);
         }  
         catch(\Exception $e)
         {
            return Helper::errorResponse($e->getMessage());
         }

    }
    public static function assignModules($id){
        $user = User::where('users.id', $id)->select('role', 'company_id')->first();
        // dd($user);
        $sub_modules = DB::table('users')
        ->join('sub_modules', function ($join) {
            $join->on(DB::raw("FIND_IN_SET(sub_modules.main_module_id, users.main_module_id)"), '>', DB::raw("0"));
        })
        ->select(
            // 'users.main_module_id as MM',
            // 'users.name',
            // 'users.id',
            'sub_modules.id',
            'sub_modules.title', 
            // 'sub_modules.route as sub_route',
            // 'sub_modules.menu_order as sub_menu_order',
            // 'sub_modules.icon as sub_icon'
        )
        ->where('users.id', '=', $id)
        ->orderBy('sub_modules.menu_order')
        ->get();
        return $sub_modules;
        // dd($sub_modules);
        // $data = self::convertToChildrens($sub_modules);
        // return $data;
    }

}
