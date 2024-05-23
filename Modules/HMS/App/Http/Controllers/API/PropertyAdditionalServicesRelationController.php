<?php

namespace Modules\HMS\App\Http\Controllers\API;

use Modules\HMS\App\Http\Controllers\Controller;
use Modules\HMS\App\Models\PropertyAdditionalServicesRelation;
use Modules\HMS\App\Http\Requests\StorePropertyAdditionalServicesRelationRequest;
use Modules\HMS\App\Http\Requests\UpdatePropertyAdditionalServicesRelationRequest;
use Modules\HMS\App\Http\Resources\PropertyAdditionalServicesRelationResource;
use Modules\HMS\App\Utilities\Helper;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class PropertyAdditionalServicesRelationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try{
            // $request->property_related_service;
            // dd($request->property_related_service);
            // $d = PropertyAdditionalServicesRelation::with(['additionalServices'])->where('property_id')->get();
            // $groupedData = collect($d)->pluck('additionalServices')->toArray();
            // return $groupedData;
            return Helper::successResponse(PropertyAdditionalServicesRelationResource::collection(PropertyAdditionalServicesRelation::all()));
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
    public function store(StorePropertyAdditionalServicesRelationRequest $request)
    {
        try{
            $propertyAdditionalServicesRelation = $request->validated();
            $userID = JWTAuth::parseToken()->authenticate();
            $propertyAdditionalServicesRelation["created_by"] = $userID->id;
            PropertyAdditionalServicesRelation::create($propertyAdditionalServicesRelation);
            return Helper::successResponse(PropertyAdditionalServicesRelationResource::make($propertyAdditionalServicesRelation));
        } catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PropertyAdditionalServicesRelation $request, $id)
    {
        try{
            
            // dd($id);
            $d = PropertyAdditionalServicesRelation::with(['additionalServices'])->where('property_id', $id)->get();
            $groupedData = collect($d)->pluck('additionalServices')->toArray();
            // return $groupedData;
            return Helper::successResponse(PropertyAdditionalServicesRelationResource::make($groupedData));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PropertyAdditionalServicesRelation $propertyAdditionalServicesRelation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePropertyAdditionalServicesRelationRequest $request, PropertyAdditionalServicesRelation $propertyAdditionalServicesRelation)
    {
        try {
            $propertyAdditionalServicesRelation_id = $request->propertyAdditionalServicesRelation;
            $propertyAdditionalServicesRelation = $request->validate();
            $userID = JWTAuth::parseToken()->authenticate();
            $propertyAdditionalServicesRelation['updated_by'] = $userID->id;
            PropertyAdditionalServicesRelation::where('id', $propertyAdditionalServicesRelation_id->id)->update($propertyAdditionalServicesRelation);
            return Helper::successResponse(PropertyAdditionalServicesRelationResource::make($propertyAdditionalServicesRelation));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PropertyAdditionalServicesRelation $propertyAdditionalServicesRelation)
    {
        try{
            if (!$propertyAdditionalServicesRelation) {
                return Helper::errorResponse('Record not found', 404);
            }

            // Set the is_deleted field to 1
            $propertyAdditionalServicesRelation->is_deleted = 1;
            $userID = JWTAuth::parseToken()->authenticate();
            $propertyAdditionalServicesRelation->deleted_by = $userID->id;
            $propertyAdditionalServicesRelation->save();

            return Helper::successResponse('Successfully Deleted', 200);
        // return Helper::successResponse('Successfully deleted',404,  response()->noContent());
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }
}
