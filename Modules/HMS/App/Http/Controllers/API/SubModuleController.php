<?php

namespace Modules\HMS\App\Http\Controllers\API;

use Modules\HMS\App\Http\Controllers\Controller;
use Modules\HMS\App\Models\SubModule;
use Modules\HMS\App\Http\Requests\StoreSubModuleRequest;
use Modules\HMS\App\Http\Requests\UpdateSubModuleRequest;
use Modules\HMS\App\Http\Resources\SubModuleResource;
use Modules\HMS\App\Utilities\Helper;
use Illuminate\Support\Arr;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class SubModuleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try { 
            // $subModule = SubModule::where('is_deleted', 0)->get();
            $User= JWTAuth::parseToken()->authenticate();
            // return $User;
            $main_module_id = explode(",",$User->main_module_id);
            $sub_modules = SubModule::whereIn('main_module_id',$main_module_id)->get();
            return Helper::successResponse(SubModuleResource::collection( $sub_modules));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSubModuleRequest $request)
    {
        try 
        { 
            $subModule=$request->validated();
            $userID = JWTAuth::parseToken()->authenticate();
            $subModule['created_by'] = $userID->id;
            // $subModule->save();
            SubModule::create($subModule);
            dd($subModule);
            return Helper::successResponse(SubModuleResource::make($subModule));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SubModule $subModule)
    {
        try 
        { 
            // if ($subModule->is_deleted == 1) {
            //     return Helper::errorResponse('Record not found', 404);
            // }
            return Helper::successResponse(SubModuleResource::make($subModule));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SubModule $subModule)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSubModuleRequest $request, SubModule $subModule)
    {
        try 
        { 
            $sub_module_id = $request->sub_module;
            $subModule = $request->validate();
            $userID = JWTAuth::parseToken()->authenticate();
            $subModule['updated_by'] = $userID->id;
            SubModule::where('id', $sub_module_id->id)->update($subModule);
            return Helper::successResponse(SubModuleResource::make($subModule));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SubModule $subModule)
    {
        try 
        { 
            // $subModule->delete();
            if (!$subModule) {
                return Helper::errorResponse('Record not found', 404);
            }
    
            // Set the is_deleted field to 1
            $subModule->is_deleted = 1;
            $userID = JWTAuth::parseToken()->authenticate();
            $subModule->deleted_by = $userID->id;
            $subModule->save();
    
            return Helper::successResponse('Successfully Deleted', 200);
            // return Helper::successResponse(response()->noContent());
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }
}
