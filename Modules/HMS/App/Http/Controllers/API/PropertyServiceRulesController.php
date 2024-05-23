<?php

namespace Modules\HMS\App\Http\Controllers\API;

use Modules\HMS\App\Http\Controllers\Controller;
use Modules\HMS\App\Models\PropertyServiceRules;
use Modules\HMS\App\Http\Requests\StorePropertyServiceRulesRequest;
use Modules\HMS\App\Http\Requests\UpdatePropertyServiceRulesRequest;
use Modules\HMS\App\Http\Resources\PropertyServiceRulesResource;
use Modules\HMS\App\Utilities\Helper;
use Modules\HMS\App\Http\Resources\BaseResource;
use Illuminate\Support\Facades\DB;
use Throwable;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class PropertyServiceRulesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try{
            return Helper::successResponse(PropertyServiceRulesResource::collection(PropertyServiceRules::all()));
        }
        catch(Throwable $th){
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
    public function store(StorePropertyServiceRulesRequest $request)
    {
        try 
        { 
            $propertyServiceRules = $request->validated();
            $user = JWTAuth::parseToken()->authenticate();
            $payload = Helper::objectsToArray($propertyServiceRules['BillingRuleForm'], ['property_service_id' => $propertyServiceRules['additional_service_id'], 'created_by' => $user->id]);
            DB::table('property_service_rules')->insert($payload);
            return Helper::successResponse(PropertyServiceRulesResource::make($payload));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PropertyServiceRules $propertyServiceRules)
    {
        try
        {
            return Helper::successResponse(PropertyServiceRules::make($propertyServiceRules));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PropertyServiceRules $propertyServiceRules)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePropertyServiceRulesRequest $request, $id)
    {
        try { 
            $propertyServiceRules = $request->validated();
            $userID = JWTAuth::parseToken()->authenticate();
            $propertyServiceRules['updated_by'] = $userID->id;
            PropertyServiceRules::where('id', $id)->update($propertyServiceRules);
            // return $propertyServiceRules;
            // $propertyServiceRules = PropertyServiceRules::with(['property'])->where('room_types.id',$id)->get();
            return Helper::successResponse(BaseResource::make($propertyServiceRules));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PropertyServiceRules $propertyServiceRules , $id)
    {
        try 
        { 
            $propertyServiceRules->find($id)->delete();
            return Helper::successResponse([], 'Successfully Deleted', 200);
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }
}
