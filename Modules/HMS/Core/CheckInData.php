<?php
namespace Modules\HMS\Core;

use Modules\HMS\App\Http\Resources\CheckInResource;
use Modules\HMS\App\Models\AssignedAdditionalServices;
use Modules\HMS\App\Models\AssignedBillingTimeRules;
use Modules\HMS\App\Models\CheckIn;
use Modules\HMS\App\Models\PropertyServiceRules;
use Modules\HMS\App\Models\PropertyServices;
use Modules\HMS\App\Utilities\Helper;
use Illuminate\Support\Facades\DB;
use Throwable;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class CheckInData{
    //function to create checkins
    public static function checkIn($checkIn, $checkin_type = null, $previous_checkin_id = null){
        DB::beginTransaction();
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $lastRecord = CheckIn::where('registeration_number', $checkIn['registeration_number'])->where('property_id', $checkIn['property_id'])->orderBy('id', 'desc')->first();
            if ($lastRecord !== null) {
                if ($lastRecord['check_in_status'] === 'active' && $checkin_type === null) {
                    return ['error' => 'Already Checked In'];
                } else {
                    $checkIn['last_check_in_id'] = $lastRecord->id;
                }
            }
            $checkIn['created_by'] = $user->id;
            $checkIn['check_in_time'] =  date("H:i:s", strtotime($checkIn['check_in_time']));
            $checkIn['expected_check_out_time'] =  date("H:i:s", strtotime($checkIn['expected_check_out_time']));
            $checkInId = CheckIn::create($checkIn);
            //TODO - need to handle if no service is selecetd.
            //Handled
            if ($checkIn['selected_services'] !== null) {
                $service_response = CheckInData::addServices($checkIn, $checkin_type, $previous_checkin_id, $checkInId, $user);
                if(isset($service_response['error'])){
                    ['error' => $service_response['error']];
                }
            }
            $checked_in_data = $checkIn['guestDetails'];

            $additional_fields_CI =  ['property_id' => $checkInId['property_id'], 'check_in_id' => $checkInId['id'], 'created_by' => $user->id];
            $CI = Helper::objectsToArray($checked_in_data, $additional_fields_CI);

            $room = collect($CI)->pluck('room_number')->unique()->toArray();
            $status = DB::table('room_lists')->whereIn('id', $room)->get();
            foreach($status as $st){
                if($st->room_status === 'occupied' && $checkin_type === null){
                    return ['error' => $st->room_number." Room is not available"];
                }
            }

            DB::table('room_lists')->whereIn('id', $room)->update(['room_status' => 'occupied', 'updated_by' => $user->id, 'check_in_date' => $checkIn['check_in_date'], 'check_out_date' => $checkIn['expected_check_out_date']]);
            DB::table("checked_in_members")->insert($CI);
            DB::commit();

            $Checked_in = CheckIn::with(['guests', 'property'])->where('check_ins.id', $checkInId->id)->get();
            return ['response' => CheckInResource::collection($Checked_in)];
        } catch (\Throwable $th) {
            DB::rollback();
            return ['error' => $th->getMessage()];
        }
    }

    //function to add services for checkins
    public static function addServices($checkIn, $checkin_type, $previous_checkin_id, $checkInId, $user){
        DB::beginTransaction();
        try{
            if($checkin_type === 're_checkin' && $previous_checkin_id !== null){
                $selected_services = AssignedAdditionalServices::where('check_in_id', $previous_checkin_id)->get(['id', 'service_name', 'basis_of_application', 'frequency', 'cost', 'selling_price'])->toArray();
                foreach($selected_services as $s_services){
                    $s_services['created_by'] = $user->id;
                    $s_services['check_in_id'] = $checkInId['id'];
                    $s_services['property_id'] = $checkInId['property_id'];
                    $billing_time_rules = AssignedBillingTimeRules::where('property_service_id', $s_services['id'])->get(['title', 'from', 'to', 'charge_compare_with', 'charge_percentage', 'apply_on'])->toArray();
                    // dd($billing_time_rules);
                    $a_id = AssignedAdditionalServices::create($s_services);
                    $additional_fields_AS =  ['property_service_id'=>$a_id->id, 'created_by'=>$user->id];
                    $ABR = Helper::objectsToArray($billing_time_rules, $additional_fields_AS);
                    // dd($ABR) ;
                    DB::table('assigned_billing_time_rules')->insert($ABR);
                }
            }
            else{
                $selected_services = explode(",", $checkIn['selected_services']);
                sort($selected_services);
                foreach ($selected_services as $s_services) {
                    $additional_services = PropertyServices::find($s_services, ['id', 'service_name', 'basis_of_application', 'frequency', 'cost', 'selling_price']);
                    $additional_services['created_by'] = $user->id;
                    $additional_services['check_in_id'] = $checkInId['id'];
                    $additional_services['property_id'] = $checkInId['property_id'];
                    $billing_time_rules = PropertyServiceRules::where('property_service_id', $s_services)->get(['title', 'from', 'to', 'charge_compare_with', 'charge_percentage', 'apply_on'])->toArray();
                    $a_id = AssignedAdditionalServices::create($additional_services->toArray());
                    $additional_fields_AS =  ['property_service_id' => $a_id->id, 'created_by' => $user->id];
                    $ABR = Helper::objectsToArray($billing_time_rules, $additional_fields_AS);
                    // dd($ABR) ;
                    DB::table('assigned_billing_time_rules')->insert($ABR);
                }
            }
            DB::commit();
            return 'OK';
        } catch(Throwable $th){
            DB::rollBack();
            return ['error' => $th->getMessage()];
        }
    }
}