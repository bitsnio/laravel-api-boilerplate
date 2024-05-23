<?php

namespace Modules\HMS\Traits;

use Modules\HMS\App\Models\CheckedInMembers;
use Modules\HMS\App\Models\CheckIn;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Carbon;
use Modules\HMS\App\Utilities\Helper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Modules\HMS\App\Models\AssignedAdditionalServices;
use Modules\HMS\App\Models\AssignedBillingTimeRules;
use Modules\HMS\App\Models\PropertyServices;
use Modules\HMS\App\Models\PropertyServiceRules;
use Hamcrest\Arrays\IsArray;
use Modules\HMS\App\Http\Requests\StoreCheckInRequest;

trait ProcessCheckin
{
    use PaymentDataCreater;

    private $debug = [];
    private $user_id;
    private $default_fields_to_exclude = ['is_deleted', 'updated_by', 'deleted_by', 'created_at', 'updated_at', 'id'];

    public function process_checkins($data, $checkin_type = "new_checkins")
    {
        $user = JWTAuth::parseToken()->authenticate();
        $this->user_id = $user->id;

        if ($checkin_type == "new_checkins") {
            // dd($data);
            $this->new_checkins($data);
        }
        
        if ($checkin_type == "re_checkins") {
            // $checkin_records = $this->validateCheckInRecords($data);
            return $this->re_checkins($data);
        }
    }




    private function validateCheckins($checkins)
    {

        $request  = new StoreCheckInRequest($checkins);
        return $request->validated();
    }

    private function new_checkins($checkIn)
    {
        $lastRecord = CheckIn::where('registeration_number', $checkIn['registeration_number'])->where('property_id', $checkIn['property_id'])->orderBy('id', 'desc')->first();
        if ($lastRecord !== null && strtolower($lastRecord['check_in_status']) === 'active'){
            throw new \Exception($checkIn['registeration_number'] . ' ' . $checkIn['family_name'] . ' Already Checked In');
        }

        $checkIn['created_by'] = $this->user_id;
        $checkIn['check_in_time'] =  $this->convertToTime($checkIn['check_in_time']);
        $checkIn['expected_check_out_time'] =  $this->convertToTime($checkIn['expected_check_out_time']);

        $Inserted_checkin = CheckIn::create($checkIn);

        // Add Additional Services Against Check In
        if ($checkIn['selected_services'] !== null) {

            $selected_services = explode(",", $checkIn['selected_services']);
            sort($selected_services);
            foreach ($selected_services as $s_services) {
                $additional_services = PropertyServices::find($s_services, ['id', 'service_name', 'basis_of_application', 'frequency', 'cost', 'selling_price']);
                $additional_services['created_by'] = $this->user_id;
                $additional_services['check_in_id'] = $Inserted_checkin['id'];
                $additional_services['property_id'] = $Inserted_checkin['property_id'];
                $billing_time_rules = PropertyServiceRules::where('property_service_id', $s_services)->get(['title', 'from', 'to', 'charge_compare_with', 'charge_percentage', 'apply_on'])->toArray();
                $a_id = AssignedAdditionalServices::create($additional_services->toArray());
                $additional_fields_AS =  ['property_service_id' => $a_id->id, 'created_by' => $this->user_id];
                $ABR = Helper::objectsToArray($billing_time_rules, $additional_fields_AS);
                DB::table('assigned_billing_time_rules')->insert($ABR);
            }
        }

        $checked_in_data = $checkIn['guestDetails'];
        if (strtolower($checkIn['check_in_type']) == 'event') {
            $checked_in_data = [];
            $event_rooms = explode(',', $checkIn['guestDetails'][0]['room_number']);
            $e_count = 1;
            foreach ($event_rooms as $e_room) {
                $event_detail = [];
                $event_detail = $checkIn['guestDetails'][0];
                $event_detail['room_number'] = $e_room;
                $event_detail['cnic_passport_number'] = $event_detail['cnic_passport_number'] . '-EVENT' . $e_count;
                $checked_in_data[] =  $event_detail;
                $e_count++;
            }
        }
        // Create and Add Guests data
        $additional_fields_CI =  ['property_id' => $Inserted_checkin['property_id'], 'check_in_id' => $Inserted_checkin['id'], 'created_by' => $this->user_id];
        $CI = Helper::objectsToArray($checked_in_data, $additional_fields_CI);

        $room = collect($CI)->pluck('room_number')->unique()->toArray();
        $status = DB::table('room_lists')->whereIn('id', $room)->get();

        foreach ($status as $st) {
            if ($st->room_status == 'occupied') {
                throw new \Exception($st->room_number . " Room is not available");
            }
        }
        DB::table('room_lists')->whereIn('id', $room)->update(['room_status' => 'occupied', 'updated_by' => $this->user_id, 'check_in_date' => $checkIn['check_in_date'], 'check_out_date' => $checkIn['expected_check_out_date']]);
        DB::table("checked_in_members")->insert($CI);
    }

    private function re_checkins($checkIndata)
    {
        if (!is_array($checkIndata)) throw new \Exception("In Case of Re-Checkins provided Data must be an array");
        foreach ($checkIndata as $checkin) {
            
            $checkin_insert = $this->filter_data_for_checkins($checkin);
            unset($checkin_insert['check_in_status']);
            unset($checkin_insert['present_status']);
            $Inserted_checkin = CheckIn::create($checkin_insert);
            $additional_services = $this->filter_data_additional_services($checkin);
            foreach ($additional_services as $service) {
                
                $services_object = $this->extract_fields($service,['property_id','service_name','basis_of_application','frequency','cost','selling_price']);
                $services_object['check_in_id'] = $Inserted_checkin->id;
                $services_object['created_by'] = $this->user_id;
                
                $a_id = AssignedAdditionalServices::create($services_object);
                $billing_rules = $this->filter_data_billing_time_rules($service,$a_id->id);
                DB::table('assigned_billing_time_rules')->insert($billing_rules->toArray());
                
            }
            $guests = (is_array($checkin['guests'])) ? $checkin['guests'] : $checkin['guests']->toArray();
            $guests_data = collect($guests)->map(function ($q){
                unset($q['age']);
                unset($q['id']);
                unset($q['updated_at']);//TODO why error occur invalid format while reverse checkout
                return $q;
            })->toArray();
            $CI = Helper::objectsToArray($guests_data, ['created_by' => $this->user_id, 'check_in_id' => $Inserted_checkin->id]);
            $rooms = collect($CI)->pluck('room_number')->toArray();
            DB::table('room_lists')->whereIn('id', $rooms)->update(['room_status' => 'occupied', 'updated_by' => $this->user_id, 'check_in_date' => $checkin['check_in_date'], 'check_out_date' => $checkin['expected_check_out_date']]);
            DB::table("checked_in_members")->insert($CI);
        }
    }


    private function filter_data_for_checkins($checkIn)
    {

        $check_in_data = $this->exclude_fields($checkIn, ['additional_services', 'guests']);
        $check_in_data['created_by'] = $this->user_id;
        return $check_in_data;

    }

    private function filter_data_additional_services($checkIn)
    {
        return  collect($this->extract_fields($checkIn, ['additional_services']))->values()->first();

    }

    private function filter_data_billing_time_rules($service,$id)
    {
        
        $array_of_rules = collect($service)->only(['property_service_rules','billing_rules'])->values()->first();
        return collect( $array_of_rules) ->map(function($rule) use ($id) {
            $billing_rule = $this->exclude_fields($rule,[]);
            $billing_rule['property_service_id'] = $id;
            $billing_rule['created_by'] = $this->user_id;
            return $billing_rule;
       });
    }

    private function filter_data_guests($checkin,$id)
    {
        
        $array_of_rules = collect($checkin)->only(['guests'])->values()->first();
        return collect( $array_of_rules) ->map(function($rule) use ($id) {
            $billing_rule = $this->exclude_fields($rule,[]);
            $billing_rule['property_service_id'] = $id;
            $billing_rule['created_by'] = $this->user->id;
            return $billing_rule;
       });
    }

    


    private function exclude_fields(array $source_object, array $fields_to_exclude)
    {
        $exclude  = collect($this->default_fields_to_exclude)->merge($fields_to_exclude);
        return collect($source_object)->except($exclude)->toArray();
    }

    private function extract_fields(array $source_object, array $fields_to_extract)
    {
        return collect($source_object)->only($fields_to_extract)->toArray();
    }
}
