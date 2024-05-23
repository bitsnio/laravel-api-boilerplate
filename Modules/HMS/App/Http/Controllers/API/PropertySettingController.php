<?php

namespace Modules\HMS\App\Http\Controllers\API;

use Modules\HMS\App\Http\Controllers\Controller;
use Modules\HMS\App\Models\PropertySetting;
use Modules\HMS\App\Http\Requests\StorePropertySettingRequest;
use Modules\HMS\App\Http\Requests\UpdatePropertySettingRequest;
use Modules\HMS\App\Http\Resources\PropertySettingResource;
use Modules\HMS\App\Models\Company;
use Modules\HMS\App\Models\Property;
use Modules\HMS\App\Utilities\Helper;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class PropertySettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(StorePropertySettingRequest $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $company_id = $user->company_id;
            // get all properties of a compay
            $properties_id = Property::where('company_id', $company_id)->get('id')->toArray();
            $property_with_settings = PropertySetting::whereIn('property_id', $properties_id)->get()->toArray();
            return Helper::successResponse(PropertySettingResource::collection($property_with_settings));
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
    public function store(StorePropertySettingRequest $request)
    {
        try{
            $propertySetting = $request->validated();
            $userID = JWTAuth::parseToken()->authenticate();
            $propertySetting["created_by"] = $userID->id;
            PropertySetting::create($propertySetting);
            return Helper::successResponse(PropertySettingResource::make($propertySetting));
        } catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PropertySetting $request, $id)
    {
        try{
            $property_with_settings = PropertySetting::where('property_id', $id)->get()->toArray();
            return Helper::successResponse(PropertySettingResource::collection($property_with_settings));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PropertySetting $propertySetting)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePropertySettingRequest $request, $id)
    {
        try {

            $propertySetting = $request->validated();
            foreach($propertySetting as $key=>$value){

                $userID = JWTAuth::parseToken()->authenticate();
                $propertySetting['updated_by'] = $userID->id;
                PropertySetting::where([['property_id', $id],['key',$key]])->update(['value'=>$value]);
            }
            
            $settings = PropertySetting::where('property_id',$id)->get();
            // return $settings;
            return Helper::successResponse(PropertySettingResource::collection($settings));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PropertySetting $propertySetting)
    {
        try{
            if (!$propertySetting) {
                return Helper::errorResponse('Record not found', 404);
            }
            // Set the is_deleted field to 1
            $propertySetting->is_deleted = 1;
            $userID = JWTAuth::parseToken()->authenticate();
            $propertySetting->deleted_by = $userID->id;
            $propertySetting->save();
            return Helper::successResponse('Successfully Deleted', 200);
        // return Helper::successResponse('Successfully deleted',404,  response()->noContent());
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }
}
