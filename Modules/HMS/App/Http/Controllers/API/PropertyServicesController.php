<?php

namespace Modules\HMS\App\Http\Controllers\API;

use Modules\HMS\App\Http\Controllers\Controller;
use Modules\HMS\App\Models\PropertyServices;
use Modules\HMS\App\Http\Requests\StorePropertyServicesRequest;
use Modules\HMS\App\Http\Requests\UpdatePropertyServicesRequest;
use Modules\HMS\App\Http\Resources\PropertyServiceResource;
use Modules\HMS\App\Utilities\Helper;
use Illuminate\Support\Facades\DB;
use Throwable;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class PropertyServicesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try{
            $additional_service = PropertyServices::with(['propertyServiceRules'])->all();
            return Helper::successResponse(PropertyServiceResource::collection( $additional_service));
    
            return Helper::successResponse(PropertyServiceResource::collection(PropertyServices::all()));
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
    public function store(StorePropertyServicesRequest $request)
    {
        DB::beginTransaction();
        try 
        { 
            //$propertyServices =$request->validated();
            $propertyServices = $request['AdditionalService'];
            $userID = JWTAuth::parseToken()->authenticate();
            
            $propertyServices['created_by'] = $userID->id;
            $service = PropertyServices::create($propertyServices);
            $billingRules = $request['billingRule'];
            $billing_rules_data = [];
            $i=0;
            if($billingRules !== null || (is_array($billingRules) && count($billingRules)!=0)){
                foreach($billingRules as $input){
                    $input['property_service_id'] = $service->id;
                    $input['created_by'] = $userID->id;
                    $billing_rules_data[$i] = $input;
                    $i++;
                }
                // dd($checked_in_members_data);
            }
            // dd($billing_rules_data);
            DB::table("property_service_rules")->insert($billing_rules_data);
            DB::commit();
            $additional_service = PropertyServices::with(['propertyServiceRules'])->where('property_services.id',$service->id)->get();
            return Helper::successResponse(PropertyServiceResource::collection( $additional_service));
    
        }
        catch (\Throwable $th) {
            DB::rollback();
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PropertyServices $propertyServices,$id)
    {
        try{
            $additional_service = PropertyServices::with(['propertyServiceRules'])->where('property_id',$id)->get();
            return Helper::successResponse(PropertyServiceResource::collection( $additional_service));
    
            // return Helper::successResponse(PropertyServices::make($propertyServices));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PropertyServices $propertyServices)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePropertyServicesRequest $request, $id)
    {
        try { 
            $propertyServices = $request->validated();
            $userID = JWTAuth::parseToken()->authenticate();
            $propertyServices['updated_by'] = $userID->id;
            PropertyServices::where('id', $id)->update($propertyServices);
            $propertyServices = PropertyServices::with(['propertyServiceRules'])->where('id',$id)->get();
            return Helper::successResponse(PropertyServiceResource::collection($propertyServices));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PropertyServices $propertyServics , $id)
    {
        try {
            $propertyServics->find($id)->delete();
            return Helper::successResponse([], 'Successfully Deleted', 200);
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }
}
