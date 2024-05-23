<?php

namespace Modules\HMS\App\Http\Controllers\API;

use Modules\HMS\App\Http\Controllers\Controller;
use Modules\HMS\App\Models\AssignedAdditionalServices;
use Modules\HMS\App\Http\Requests\StoreAssignedAdditionalServicesRequest;
use Modules\HMS\App\Http\Requests\UpdateAssignedAdditionalServicesRequest;
use Modules\HMS\App\Http\Resources\AssignedAdditionalServicesResource;
use Modules\HMS\App\Models\AssignedBillingTimeRules;
use Modules\HMS\App\Models\CheckIn;
use Modules\HMS\App\Utilities\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AssignedAdditionalServicesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try 
        { 
            // $AssignedAdditionalServices = AdditionalServices::where('is_deleted', 0)->get();
            return Helper::successResponse(AssignedAdditionalServicesResource::collection(AssignedAdditionalServices::all()));
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
    public function store(StoreAssignedAdditionalServicesRequest $request)
    {
        try 
        { 
            $AssignedAdditionalServices =$request->validated();
            $userID = JWTAuth::parseToken()->authenticate();
            $AssignedAdditionalServices['created_by'] = $userID->id;
            // $AssignedAdditionalServices->save();
            // dd($request);
            AssignedAdditionalServices::create($AssignedAdditionalServices);
            return Helper::successResponse(AssignedAdditionalServicesResource::make($AssignedAdditionalServices));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(AssignedAdditionalServices $assignedAdditionalServices)
    {
        try 
        { 
            return Helper::successResponse(AssignedAdditionalServicesResource::make($assignedAdditionalServices));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AssignedAdditionalServices $assignedAdditionalServices)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAssignedAdditionalServicesRequest $request, AssignedAdditionalServices $assignedAdditionalServices)
    {
        try 
        { 
            $assigned_additional_service_id = $request->assigned_additional_service;
            $assignedAdditionalServices = $request->validate();
            $userID = JWTAuth::parseToken()->authenticate();
            $assignedAdditionalServices['updated_by'] = $userID->id;
            AssignedAdditionalServices::where('id', $assigned_additional_service_id->id)->update($assignedAdditionalServices);
            return Helper::successResponse(AssignedAdditionalServicesResource::make($assignedAdditionalServices));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AssignedAdditionalServices $assignedAdditionalServices)
    {
        try 
        { 
            // $assignedAdditionalServices->delete();
            if (!$assignedAdditionalServices) {
                return Helper::errorResponse('Record not found', 404);
            }
    
            // Set the is_deleted field to 1
            $assignedAdditionalServices->is_deleted = 1;
            $userID = JWTAuth::parseToken()->authenticate();
            $assignedAdditionalServices->deleted_by = $userID->id;
            $assignedAdditionalServices->save();
    
            return Helper::successResponse('Successfully Deleted', 200);
            // return Helper::successResponse(response()->noContent());
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    //Update assigned additional services and billing rules for activen checkins
    public function updateServicesAndRules(Request $request){
        DB::beginTransaction();
        try{
            $user = JWTAuth::parseToken()->authenticate();
            $query = CheckIn::with(['additionalServices', 'properties'])
            ->whereHas('properties', function ($q) use ($user) {
                $q->where('company_id', '=', $user->company_id);
                $q->where('is_deleted', '=', 0);
            });
            if(isset($request->property_id)){
                $query->where('property_id', $request->property_id);
                if(isset($request->check_in_ids)){
                    $query->whereIn('id', $request->check_in_ids);
                }
            }
            $check_ins = $query->get()->toArray();
            $check_in_ids = collect($check_ins)->pluck('id')->toArray();
            $assigned_services = collect($check_ins)->pluck('additional_services')->toArray();
            $service_ids = collect($assigned_services)->pluck('id')->toArray();
            AssignedAdditionalServices::whereIn('check_in_id', $check_in_ids)->delete();
            AssignedBillingTimeRules::whereIn('property_service_id', $service_ids)->delete();
        }
        catch(Throwable $th){
            return Helper::errorResponse($th->getMessage());
        }
    }
}
