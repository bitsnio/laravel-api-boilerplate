<?php
namespace Modules\HMS\Core;

use Modules\HMS\App\Http\Controllers\API\CheckInController;
use Modules\HMS\App\Models\AssignedAdditionalServices;
use Modules\HMS\App\Models\AssignedBillingTimeRules;
use Modules\HMS\App\Models\CheckedInMembers;
use Modules\HMS\App\Models\CheckIn;
use Modules\HMS\App\Models\familyGeneratedBill;
use Modules\HMS\App\Models\Payable;
use Modules\HMS\App\Models\Property;
use Modules\HMS\App\Models\PropertyBilling;
use Modules\HMS\App\Models\PropertyServiceRules;
use Modules\HMS\App\Models\PropertyServices;
use Modules\HMS\App\Models\Receipt;
use Modules\HMS\App\Models\RoomList;
use Modules\HMS\Traits\ProcessCheckin;
use Modules\HMS\App\Utilities\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class PermanentDelete {
    use ProcessCheckin;
     //function to delete permanent delete checkout and its all corrosponding data and recheckin it
     public function deleteCheckout(Request $request){
        DB::beginTransaction();
        try{
            $user = JWTAuth::parseToken()->authenticate();
            $check_in_ids =  $request['checkin_ids'];
            if(!isset($request['checkin_ids']) || !is_array($request['checkin_ids'])){
                return Helper::errorResponse('request is incomplete');
            }
            
            $checkins =  CheckIn::with(['additionalServices', 'additionalServices.billingRules','guests'])->whereIn('id', $check_in_ids)->get()->toArray();
            $isActive_checkins = collect($checkins)->where('check_in_status','active');
            if(empty($checkins)) return Helper::errorResponse('No record found, selected check-outs may already reversed');
            if(count($isActive_checkins) > 0 ) return Helper::errorResponse('Some of selected checkins are active and cannot be reversed');
            $room_ids = collect($checkins)->pluck('guests')->flatten(1)->pluck('room_number')->unique()->toArray();
            $occupied_rooms = RoomList::whereIn('id', $room_ids)->where('room_status', 'occupied')->get()->toArray();
            
            collect($checkins)->map(function($checkin) use ($user, $occupied_rooms){

                //check if requested any re-check-in is created
                $active_checkins = Checkin::where('registeration_number', $checkin['registeration_number'])->where('check_in_status', 'active')->get()->toArray();
                if(!empty($active_checkins)){
                    $duplicate_checkins = collect($active_checkins)->where('property_id',  $checkin['property_id'])->first();
                    if($duplicate_checkins != null){
                        throw new \Exception('Cannot reverse check-out, '.$checkin['registeration_number'].' '.$checkin['family_name']. ' is currently active in same property');
                    }
                    $previous_checkin = collect($active_checkins)->where('last_checkin_id',  $checkin['id'])->first();
                    if($previous_checkin != null){
                        throw new \Exception('Cannot reverse check-out, '.$checkin['registeration_number'].' '.$checkin['family_name']. ' is re-checked-in and currently active');
                    }
                }
                
                $check_in_id = $checkin['id'];
                //check if any room is not available for requested check-out
                $family_room_ids = collect($checkin['guests'])->pluck('room_number')->unique()->toArray();
                $occupied_family_rooms = collect($occupied_rooms)->whereIn('id', $family_room_ids)->pluck('room_number')->toArray();
                if(!empty($occupied_family_rooms)){
                    $cooupied_family_room_numbers = implode(', ',$occupied_family_rooms);
                    throw new \Exception('Cannot reverse check-out '.$checkin['registeration_number'].' '.$checkin['family_name'].' as following rooms '.$cooupied_family_room_numbers.' are not available');
                }

                //check if batch is created for requested check-out
                $batch_records = Receipt::select('check_in_ids', 'id')->orWhereRaw("FIND_IN_SET($check_in_id,check_in_ids)")->first();
                if($batch_records != null){
                    throw new \Exception('Cannot reverse check-out, batch created for '.$checkin['registeration_number'].' '.$checkin['family_name']);
                }
                
                //check if requested check-out is merged
                $merge_records = familyGeneratedBill::select('check_in_ids', 'id')->orWhereRaw("FIND_IN_SET($check_in_id,check_in_ids)")->first();
                if($merge_records != null){
                    $merge_ids =  array_diff(explode(',',$merge_records['check_in_ids']), [$check_in_id]);
                    if(!empty($merge_ids)){
                        familyGeneratedBill::where('id', $merge_records['id'])->update(['check_in_ids' => implode(',', $merge_ids), 'updated_by' => $user->id]);
                    }
                    else{
                        $merge_records->delete();
                    }
                }
            });

            CheckIn::whereIn('id', $check_in_ids)->delete();
            CheckedInMembers::whereIn('check_in_id', $check_in_ids)->delete();
            Payable::whereIn('check_in_id', $check_in_ids)->delete();
            PropertyBilling::whereIn('check_in_id', $check_in_ids)->delete();
            $assigned_service_ids = AssignedAdditionalServices::whereIn('check_in_id', $check_in_ids)->get()->pluck('id')->toArray();
            AssignedAdditionalServices::whereIn('check_in_id', $check_in_ids)->delete();
            AssignedBillingTimeRules::whereIn('property_service_id', $assigned_service_ids)->delete();
            $this->process_checkins($checkins, 're_checkins');
            DB::commit();
            return Helper::successResponse(null, 'checkouts reversed successfully');
        } catch(Throwable $th){
            DB::rollBack();
            return Helper::errorResponse($th->getMessage());
        }
     }

     //copy current property services with rules and assign to all active checkins
     public static function assignCurrentServices(Request $request){
        DB::beginTransaction();
        try{
            $user = JWTAuth::parseToken()->authenticate();
            $query = CheckIn::where('check_in_status', 'active');
            if((!isset($request['property_id']) || $request['property_id'] == null) && (!isset($request['check_in_ids']) || $request['check_in_ids'] == null)){
                return Helper::errorResponse('Request is incomplete, please select a valid property or check-ins to apply latest services and billing rules');
            }
            if(isset($request['property_id']) && $request['property_id'] != null){
                $properties = Property::where('id', $request['property_id'])->where('company_id', $user->company_id)->first();
                if($properties == null) return Helper::errorResponse('no property found against given id');
                $query->where('property_id', $request['property_id']);
            }
            if(isset($request['check_in_ids'])) $query->whereIn('id', $request['check_in_ids']);
            $check_ins = $query->get()->toArray();
            if(empty($check_ins)) return Helper::errorResponse('no active check-ins found');
            $checkin_ids = collect($check_ins)->pluck('id')->toArray();
            $assigned_services = AssignedAdditionalServices::whereIn('check_in_id', $checkin_ids)->get()->pluck('id')->toArray();
            AssignedAdditionalServices::whereIn('check_in_id', $checkin_ids)->delete();
            AssignedBillingTimeRules::whereIn('property_service_id', $assigned_services)->delete();
            $service_billing_rules = [];
            foreach($check_ins as $data){
                $selected_services = explode(",", $data['selected_services']);
                sort($selected_services);
                foreach ($selected_services as $s_services) {
                    $additional_services = PropertyServices::find($s_services, ['id', 'service_name', 'basis_of_application', 'frequency', 'cost', 'selling_price'])->toArray();
                    $additional_services['created_by'] = $user->id;
                    $additional_services['check_in_id'] = $data['id'];
                    $additional_services['property_id'] = $data['property_id'];
                    $billing_time_rules = PropertyServiceRules::where('property_service_id', $s_services)->get(['title', 'from', 'to', 'charge_compare_with', 'charge_percentage', 'apply_on'])->toArray();
                    $a_id = AssignedAdditionalServices::create($additional_services);
                    $additional_fields_AS =  ['property_service_id' => $a_id->id, 'created_by' => $user->id];
                    $ABR = Helper::objectsToArray($billing_time_rules, $additional_fields_AS);
                    $service_billing_rules[] = $ABR;
                }
            }
            $service_billing_rules = collect($service_billing_rules)->flatten(1)->toArray();
            DB::table('assigned_billing_time_rules')->insert($service_billing_rules);
            DB::commit();
            return Helper::successResponse(null, 'successfully applied current property services and billing rules on all active checkins');
        }catch(Throwable $th){
            DB::rollBack();
            return Helper::errorResponse($th->getMessage());
        }
     }

  
}