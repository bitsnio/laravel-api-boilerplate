<?php

namespace Modules\HMS\App\Http\Controllers\API;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\HMS\App\Utilities\Helper;
use Modules\HMS\App\Models\MainModule;
use Database\Factories\MainModuleFactory;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Modules\HMS\App\Http\Controllers\Controller;
use Modules\HMS\App\Http\Resources\MainModuleResource;
use Modules\HMS\App\Http\Requests\StoreMainModuleRequest;
use Modules\HMS\App\Http\Requests\UpdateMainModuleRequest;

class MainModuleController extends Controller {
    /**
    * Display a listing of the resource.
    */

    public function index() {
        // return MainModuleResource::collection( MainModule::all() );
        // $data = MainModuleResource::collection( MainModule::all() );
        try {

            // $mainModule = MainModule::where( 'is_deleted', 0 )->get();
            return Helper::successResponse( MainModuleResource::collection( MainModule::all() ) );
        } catch ( \Throwable $th ) {
            return Helper::errorResponse( $th->getMessage() );
        }
    }

    /**
    * Show the form for creating a new resource.
    */

    public function create() {
        //
    }

    /**
    * Store a newly created resource in storage.
    */

    public function store( StoreMainModuleRequest $request ) {
        // $mainModule = MainModule::create( $request->validated() );
        // return MainModuleResource::make( $mainModule );
        try {

            $mainModule = $request->validated();
            $userID = JWTAuth::parseToken()->authenticate();
            $mainModule[ 'created_by' ] = $userID->id;
            // dd( $mainModule );
            // $mainModule->save();
            MainModule::create( $mainModule );
            return Helper::successResponse( MainModuleResource::make( $mainModule ) );
        } catch ( \Throwable $th ) {
            return Helper::errorResponse( $th->getMessage() );
        }
    }

    /**
    * Display the specified resource.
    */

    public function show( MainModule $mainModule ) {
        // return MainModuleResource::make( $mainModule );
        // $data = MainModuleResource::make( $mainModule );
        try {

            // if ( $mainModule->is_deleted == 1 ) {
            //     return Helper::errorResponse( 'Record not found', 404 );
            // }
            return Helper::successResponse( MainModuleResource::make( $mainModule ) );
        } catch ( \Throwable $th ) {
            return Helper::errorResponse( $th->getMessage() );
        }
    }

    /**
    * Show the form for editing the specified resource.
    */

    public function edit( MainModule $mainModule ) {
        //
    }

    /**
    * Update the specified resource in storage.
    */

    public function update( UpdateMainModuleRequest $request, MainModule $mainModule ) {
        try {

            $module_id = $request->module;
            $mainModule = $request->validate();
            $userID = JWTAuth::parseToken()->authenticate();
            $mainModule[ 'updated_by' ] = $userID->id;
            MainModule::where( 'id', $module_id->id )->update( $mainModule );
            return Helper::successResponse( MainModuleResource::make( $mainModule ) );
        } catch ( \Throwable $th ) {
            return Helper::errorResponse( $th->getMessage() );
        }

    }

    /**
    * Remove the specified resource from storage.
    */

    public function destroy( MainModule $mainModule, $id ) {
        DB::beginTransaction();
        try {
            // dd( $mainModule->toArray(), $id );
            // dd( $mainModule->toArray() );
            if ( !$mainModule ) {
                return Helper::errorResponse( 'Record not found', 404 );
            }
            // Set the is_deleted field to 1
            // $mainModule->is_deleted = 1;
            $userID = JWTAuth::parseToken()->authenticate();
            $mainModule = MainModule::findOrFail( $id );
            $mainModule->deleted_by = $userID->id;
            $mainModule->save();
            $mainModule->delete();
            DB::commit();
            return Helper::successResponse( 'Successfully Deleted', 200 );
            // return Helper::successResponse( 'Successfully deleted', 404,  response()->noContent() );
        } catch ( \Throwable $th ) {
            return Helper::errorResponse( $th->getMessage() );
        }
    }
}
