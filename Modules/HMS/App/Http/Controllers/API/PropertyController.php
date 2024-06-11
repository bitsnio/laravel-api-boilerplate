<?php

namespace Modules\HMS\App\Http\Controllers\API;

use Modules\HMS\App\Http\Controllers\Controller;
use Modules\HMS\App\Models\Property;
use Modules\HMS\App\Models\PropertyAdditionalServicesRelation;
use Modules\HMS\App\Http\Requests\StorePropertyRequest;
use Modules\HMS\App\Http\Requests\UpdatePropertyRequest;
use Modules\HMS\App\Http\Resources\DashboardReource;
use Modules\HMS\App\Http\Resources\DashboardResource;
use Modules\HMS\App\Http\Resources\PropertyGetResource;
use Illuminate\Http\Request;
use Modules\HMS\App\Http\Resources\PropertyResource;
use Modules\HMS\App\Models\AdditionalServices;
use Modules\HMS\App\Models\BillingTimeRule;
use Modules\HMS\App\Models\CheckIn;
use Modules\HMS\App\Models\Payable;
use Modules\HMS\App\Models\PropertyBilling;
use Modules\HMS\App\Models\PropertyServiceRules;
use Modules\HMS\App\Models\PropertyServices;
use Modules\HMS\App\Models\RoomList;
use Modules\HMS\App\Models\RoomType;
use Modules\HMS\App\Utilities\Helper;
use Modules\HMS\Database\Seeders\PropertySettingsSeeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class PropertyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try { 
            // dd($request->toArray());
            // $property = Property::where('is_deleted', 0)->get();
            $userId = JWTAuth::parseToken()->authenticate();
            $properties = Property::with(['propertyServices'])->where('company_id', $userId->company_id)
            ->where(function ($q) use ($request) {
                if ($request->has('property_type')) {
                    $q->where('property_type', $request->input('property_type'));
                }
                if ($request->has('category')) {
                    $q->where('category', $request->input('category'));
                }
            })->get();
            // return $properties;
            return Helper::successResponse(PropertyResource::collection($properties));
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
    public function store(StorePropertyRequest $request)
    {
        DB::beginTransaction();
        try { 
            $property = $request->validated();
            $userId = JWTAuth::parseToken()->authenticate();
            $property["created_by"] = $userId->id;
            $property["company_id"] = $userId->company_id;
            $newProperty = Property::create($property);
            if(isset($property['additional_services']) && $property['additional_services'] !== null){
                $selected_services = explode(",",$property['additional_services']);
                sort($selected_services);
                foreach($selected_services as $s_services){
                    $additional_services = AdditionalServices::find($s_services,['id', 'service_name', 'basis_of_application', 'frequency', 'cost', 'selling_price']);
                    $additional_services['created_by'] = $userId->id;
                    $additional_services['property_id'] = $newProperty->id;
                    $billing_time_rules = BillingTimeRule::where('additional_service_id', $s_services)->get(['title', 'from', 'to', 'charge_compare_with', 'charge_percentage', 'apply_on'])->toArray();
                    $a_id = PropertyServices::create($additional_services->toArray());
                    $additional_fields_AS =  ['property_service_id'=>$a_id->id, 'created_by'=>$userId->id];
                    $ABR = Helper::objectsToArray($billing_time_rules, $additional_fields_AS);
                    // dd($ABR);
                    DB::table('property_service_rules')->insert($ABR);
                }
            }
            $seeder = new PropertySettingsSeeder;
            $result = $seeder->run($newProperty->id);
            if($result['success']) {
                DB::commit();
                return Helper::successResponse(PropertyResource::make($newProperty), 'Property '.$newProperty->property_name.' successfully created');
            }
            else{
                return Helper::errorResponse($result['message']);
            }
        }
        catch(\Throwable $th) {
            DB::rollBack();
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Property $property)
    {
        try 
        { 
            $userId = JWTAuth::parseToken()->authenticate();
            if ($property->company_id != $userId->company_id) {
                return Helper::errorResponse('Record not found', 404);
            }
            return Helper::successResponse(PropertyResource::make($property));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Property $property)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePropertyRequest $request, Property $property)
    {
        DB::beginTransaction();
        try 
        { 
            $property_id = $request->property;
            $property = $request->validated();
            $userID = JWTAuth::parseToken()->authenticate();
            $property['updated_by'] = $userID->id;
            $additional_services = explode(",",$property['additional_services']);
            unset($property['additional_services']);
            Property::where('id', $property_id->id)->update($property);
            // update property_additional_services_relations
            
            $availableServices = PropertyServices::where('property_id',$property_id->id)
            ->pluck('id')->toArray();
      
            $idsToDelete = collect($availableServices)->diff($additional_services);
            $IdsToAdd = collect($additional_services)->diff($availableServices);
           // delete property services and their billing rules as well
            if(count($idsToDelete) != 0){
                PropertyServices::where(['property_id'=>$property_id->id])->whereIn('id',$idsToDelete)->delete();
                PropertyServiceRules::whereIn('property_service_id',$idsToDelete)->delete();
            }
            
            if(count($IdsToAdd) != 0){
                // $create_array = collect($IdsToAdd)->map(function ($id) use ($property_id,$userID){
                //     return ['property_id'=>$property_id->id,'additional_service_id'=>$id,'created_by'=>$userID->id];
                // })->toArray();
                // return $create_array;
                foreach($IdsToAdd as $s_services){
                    $additional_services = AdditionalServices::find($s_services,['id', 'service_name', 'basis_of_application', 'frequency', 'cost', 'selling_price']);
                    $additional_services['created_by'] = $userID->id;
                    $additional_services['property_id'] = $property_id->id;
                    $billing_time_rules = BillingTimeRule::where('additional_service_id', $s_services)->get(['title', 'from', 'to', 'charge_compare_with', 'charge_percentage', 'apply_on'])->toArray();
                    $a_id = PropertyServices::create($additional_services->toArray());
                    $additional_fields_AS =  ['property_service_id'=>$a_id->id, 'created_by'=>$userID->id];
                    $ABR = Helper::objectsToArray($billing_time_rules, $additional_fields_AS);
                    // dd($ABR);
                    DB::table('property_service_rules')->insert($ABR);
                }                
            }

            $properties = Property::with(['propertyServices'])->where('id', $property_id->id)->get();
            DB::commit();
            return Helper::successResponse(PropertyResource::collection($properties));
           
        }
        catch (\Throwable $th) {
            DB::rollBack();
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Property $property)
    {
        DB::beginTransaction();
        try 
        { 
            // dd($property);
            $user = JWTAuth::parseToken()->authenticate();
            $property_checkins = CheckIn::where('property_id', $property->id)->where('check_in_status', 'active')->get()->toArray();
            if(!empty($property_checkins)){
                return Helper::errorResponse($property->property_name.' cannot be deleted, first checkout the active checkins');
            }
            $property_services = PropertyServices::where('property_id', $property->id)->get()->pluck('id')->toArray();
            PropertyServices::whereIn('id', $property_services)->update(['is_deleted' => 1, 'deleted_by' => $user->id]);
            PropertyServiceRules::whereIn('property_service_id', $property_services)->update(['is_deleted' => 1, 'deleted_by' => $user->id]);
            RoomType::where('property_id', $property->id)->update(['is_deleted' => 1, 'deleted_by' => $user->id]);
            RoomList::where('property_id', $property->id)->update(['is_deleted' => 1, 'deleted_by' => $user->id]);
            // Set the is_deleted field to 1
            $property->is_deleted = 1;
            $property->deleted_by = $user->id;
            $property->save();
            DB::commit();
            return Helper::successResponse('Successfully Deleted', 200);
            // return Helper::successResponse(response()->noContent());
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    public function dashboardData(Request $request){
        try{
            $user = JWTAuth::parseToken()->authenticate();
            $properties = Property::where('company_id', $user->company_id)->get()->toArray();
            $property_ids = collect($properties)->pluck('id')->toArray();
            if($request->start_date !== null && $request->end_date !== null){
                $checkins = CheckIn::whereIn('property_id', $property_ids)->whereBetween('check_in_date', [$request->start_date, $request->end_date])->get()->toArray();
            }
            else if($request->start_date !== null && $request->end_date === null){
                $checkins = CheckIn::whereIn('property_id', $property_ids)->where('check_in_date', '>=', $request->start_date)->get()->toArray();
            }
            else if($request->start_date === null && $request->end_date !== null){
                $checkins = CheckIn::whereIn('property_id', $property_ids)->where('check_in_date', '<=', $request->end_date)->get()->toArray();
            }
            else{
                $checkins = CheckIn::whereIn('property_id', $property_ids)->get()->toArray();
            }
            $active_checkins = collect($checkins)->where('check_in_status', 'active')->count();
            $total_checkins = count($checkins);
            $billing_ids = collect($checkins)->where('check_in_status', 'checked_out')->pluck('id')->toArray();
            $payables = Payable::whereIn('check_in_id', $billing_ids)->get()->toArray();
            $total_payables = collect($payables)->sum('total_amount');
            $pending_payables = collect($payables)->where('payment_status', 0)->sum('total_amount');
            $cleared_payables =  $total_payables - $pending_payables;
            $receivables  = PropertyBilling::whereIn('check_in_id', $billing_ids)->get()->toArray();
            $total_receivables = collect($receivables)->sum('total_amount');
            $pending_receivables = collect($receivables)->where('payment_status', 0)->sum('total_amount');
            $cleared_receivables =  $total_receivables - $pending_receivables;
            $dashboard_data['total_properties'] = count($properties);
            $dashboard_data['total_checkins'] = $total_checkins;
            $dashboard_data['active_checkins'] = $active_checkins;
            $dashboard_data['total_payables'] = $total_payables;
            $dashboard_data['pending_payables'] = $pending_payables;
            $dashboard_data['cleared_payables'] = $cleared_payables;
            $dashboard_data['total_receivables'] = $total_receivables;
            $dashboard_data['pending_receivables'] = $pending_receivables;
            $dashboard_data['cleared_receivables'] = $cleared_receivables;
            return Helper::successResponse($dashboard_data);
        }
        catch(Throwable $th){
            return Helper::errorResponse($th->getMessage());
        }
    }
}
