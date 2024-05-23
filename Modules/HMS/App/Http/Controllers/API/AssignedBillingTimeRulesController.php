<?php

namespace Modules\HMS\App\Http\Controllers\API;

use Modules\HMS\App\Http\Controllers\Controller;
use Modules\HMS\App\Models\AssignedBillingTimeRules;
use Modules\HMS\App\Http\Requests\StoreAssignedBillingTimeRulesRequest;
use Modules\HMS\App\Http\Requests\UpdateAssignedBillingTimeRulesRequest;
use Modules\HMS\App\Http\Resources\AssignedBillingTimeRulesResource;
use Modules\HMS\App\Utilities\Helper;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AssignedBillingTimeRulesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try { 
            return Helper::successResponse(AssignedBillingTimeRulesResource::collection(AssignedBillingTimeRules::all()));
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
    public function store(StoreAssignedBillingTimeRulesRequest $request)
    {
        try 
        { 
            $assignedBillingTimeRules = $request->validated();
            $userId = JWTAuth::parseToken()->authenticate();
            $assignedBillingTimeRules['created_by'] = $userId->id;
            // $assignedBillingTimeRules->save();
            AssignedBillingTimeRules::create($assignedBillingTimeRules);
            return Helper::successResponse(AssignedBillingTimeRulesResource::make($assignedBillingTimeRules));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(AssignedBillingTimeRules $assignedBillingTimeRules)
    {
        try 
        { 
            return Helper::successResponse(AssignedBillingTimeRulesResource::make($assignedBillingTimeRules));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AssignedBillingTimeRules $assignedBillingTimeRules)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAssignedBillingTimeRulesRequest $request, AssignedBillingTimeRules $assignedBillingTimeRules)
    {
        try 
        { 
            // dd(1111);
            $assigned_billing_time_rule_id = $request->assigned_billing_time_rule;
            $assignedBillingTimeRules = $request->validate();
            $userID = JWTAuth::parseToken()->authenticate();
            $assignedBillingTimeRules['updated_by'] = $userID->id;
            AssignedBillingTimeRules::where('id', $assigned_billing_time_rule_id->id)->update($assignedBillingTimeRules);
            return Helper::successResponse(AssignedBillingTimeRulesResource::make($assignedBillingTimeRules));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AssignedBillingTimeRules $assignedBillingTimeRules)
    {
        try 
        { 
            // $assignedBillingTimeRules->delete();
            if (!$assignedBillingTimeRules) {
                return Helper::errorResponse('Record not found', 404);
            }
    
            // Set the is_deleted field to 1
            $assignedBillingTimeRules->is_deleted = 1;
            $userId = JWTAuth::parseToken()->authenticate();
            $assignedBillingTimeRules->deleted_by = $userId->id;
            $assignedBillingTimeRules->save();
    
            return Helper::successResponse('Successfully Deleted', 200);
            // return Helper::successResponse(response()->noContent());
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }
}
