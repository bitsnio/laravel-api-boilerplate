<?php

namespace Modules\HMS\App\Http\Controllers\API;

use Modules\HMS\App\Http\Controllers\Controller;
use Modules\HMS\App\Models\CheckIn;
use Modules\HMS\App\Http\Requests\StoreCheckInRequest;
use Modules\HMS\App\Http\Requests\UpdateCheckInRequest;
use Modules\HMS\App\Http\Resources\CheckInResource;
use Modules\HMS\App\Models\AssignedAdditionalServices;
use Modules\HMS\App\Models\AssignedBillingTimeRules;
use Modules\HMS\App\Models\CheckedInMembers;
use Modules\HMS\App\Models\Payable;
use Modules\HMS\App\Models\Property;
use Modules\HMS\App\Models\PropertyBilling;
use Modules\HMS\App\Models\PropertyServiceRules;
use Modules\HMS\App\Models\PropertyServices;
use Modules\HMS\App\Models\PropertySetting;
use Modules\HMS\App\Models\RoomList;
use Modules\HMS\App\Models\RoomType;
use App\Models\User;
use App\Models\UserRole;
use Modules\HMS\Traits\PaymentDataCreater;
use Modules\HMS\Traits\ProcessCheckin;
use Modules\HMS\App\Utilities\Helper;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Throwable;
class CheckInController extends Controller
{
    use ProcessCheckin;
    use PaymentDataCreater;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $company_id = $user->company_id;
            $query = CheckIn::with(['guests.roomDetails', 'properties'])
                ->whereHas('properties', function ($q) use ($company_id) {
                    $q->where('company_id', '=', $company_id);
                });
                if ($request->has('check_in_status')) {
                $query->where('check_in_status', $request->input('check_in_status'));
            }
            if ($request->has('bound_country')) {
                $query->where('bound_country', $request->input('bound_country'));
            }
            if ($request->has('property_id')) {
                $query->where('property_id', $request->input('property_id'));
            }
            $result = $query->get();
            $other_info = [];
            $check_in_ids = collect($result->toArray())->pluck('id')->toArray();
            $members = CheckedInMembers::whereIn('check_in_id', $check_in_ids)->get()->count();
            if ($request->has('property_id')) {
                $room_list = RoomList::where('property_id', $request->input('property_id'))->get()->toArray();
                $other_info['total_guests'] = $members;
                $other_info['total_rooms'] = collect($room_list)->count();
                $other_info['available_rooms'] = collect($room_list)->where('room_status', 'available')->count();
                $other_info['occupied_rooms'] = $other_info['total_rooms'] - $other_info['available_rooms'];
            }
            else{
                $properties = Property::where('company_id', $company_id)->get()->pluck('id')->toArray();
                $room_list = RoomList::whereIn('property_id', $properties)->get()->toArray();
                $other_info['total_properties'] = count($properties);
                $other_info['total_guests'] = $members;
                $other_info['total_rooms'] = collect($room_list)->count();
                $other_info['available_rooms'] = collect($room_list)->where('room_status', 'available')->count();
                $other_info['occupied_rooms'] = $other_info['total_rooms'] - $other_info['available_rooms'];
            }
            return Helper::successResponse( ['check_ins' => CheckInResource::collection($result), 'other_info' => $other_info]);
        } catch (\Throwable $th) {
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
    public function store(StoreCheckInRequest $request)
    {
        DB::beginTransaction();
        try {
            $checkIn = $request->validated();
            $this->process_checkins($checkIn);
            DB::commit();
            return Helper::successResponse($checkIn, 'Successfully checked in');
        } catch (Throwable $th) {
            DB::rollBack();
            return Helper::errorResponse($th->getMessage());
        }
    }

    //checkin function to create checkins
    public function checkIn($checkIn, $checkin_type = null, $previous_checkin_id = null)
    {
        DB::beginTransaction();
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $lastRecord = CheckIn::where('registeration_number', $checkIn['registeration_number'])->where('property_id', $checkIn['property_id'])->orderBy('id', 'desc')->first();
            if ($lastRecord !== null) {
                if ($lastRecord['check_in_status'] == 'active' && $checkin_type == null) {
                    return ['error' => $checkIn['registeration_number'].' '.$checkIn['family_name'].' Already Checked In'];
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
                $service_response = $this->addServices($checkIn, $checkin_type, $previous_checkin_id, $checkInId, $user);
                if(isset($service_response['error'])){
                    ['error' => $service_response['error']];
                }
            }
            $checked_in_data = $checkIn['guestDetails'];
            if(strtolower($checkIn['check_in_type']) == 'event' && strtolower($checkin_type) == null) {
                $checked_in_data = [];
                $event_rooms = explode(',', $checkIn['guestDetails'][0]['room_number']);
                $e_count = 1;
                foreach($event_rooms as $e_room){
                    $event_detail = [];
                    $event_detail = $checkIn['guestDetails'][0];
                    $event_detail['room_number'] = $e_room;
                    $event_detail['cnic_passport_number'] = $event_detail['cnic_passport_number'].'-EVENT'.$e_count;
                    $checked_in_data[] =  $event_detail;
                    $e_count++;
                }
            }
            $additional_fields_CI =  ['property_id' => $checkInId['property_id'], 'check_in_id' => $checkInId['id'], 'created_by' => $user->id];
            $CI = Helper::objectsToArray($checked_in_data, $additional_fields_CI);
          
            $room = collect($CI)->pluck('room_number')->unique()->toArray();
            $status = DB::table('room_lists')->whereIn('id', $room)->get();

            foreach($status as $st){
                if($st->room_status == 'occupied' && $checkin_type == null){
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
    /**
     * Display the specified resource.
     */
    public function show(CheckIn $checkIn)
    {
        try {
            // if ($checkIn->is_deleted == 1) {
            //     return Helper::errorResponse('Record not found', 404);
            // }
            return Helper::successResponse(CheckInResource::make($checkIn));
        } catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CheckIn $checkIn)
    {
        // 
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCheckInRequest $request, $id)
    {
    //  dd($request->property_id); 
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['check_in_time'] =  date("H:i:s", strtotime($data['check_in_time']));
            if(isset($data['expected_check_out_time']) && $data['expected_check_out_time'] != null) $data['expected_check_out_time'] =  date("H:i:s", strtotime($data['expected_check_out_time']));
            // if($checkIn['check_in_status'] == 'checked_out') return Helper::errorResponse('checked out data cannot be updated');
            $user = JWTAuth::parseToken()->authenticate();
            if(isset($data['guestDetails']) && $data['guestDetails'] != null){
                $guest_details = $data['guestDetails'];
                $old_rooms = CheckedInMembers::where('check_in_id', $id)->get()->pluck('room_number')->unique()->toArray();
                $current_rooms = collect($guest_details)->pluck('room_number')->unique()->toArray();
                $new_rooms = array_diff($current_rooms, $old_rooms);
                if(!empty($new_rooms)) return Helper::errorResponse('New rooms cannot be allocated, only family rooms are allowed');
                $empty_rooms = array_diff($old_rooms, $current_rooms);
                if(!empty($empty_rooms)) RoomList::whereIn('id', $empty_rooms)->update(['room_status' => 'available', 'updated_by' => $user->id, 'check_in_date' => null, 'check_out_date' => null]);
                $data['updated_by'] = $user->id;
                if(is_array($guest_details)){
                    foreach($guest_details as $guest){
                        $guest['updated_by'] = $user->id;
                        CheckedInMembers::where('check_in_id', $id)->where('cnic_passport_number', $guest['cnic_passport_number'])->update($guest);
                    }
                }
            }
            if(isset($data['selected_services']) && $data['selected_services'] != null){
                $array1 = array_diff(explode(',', $request['selected_services']),explode(',', $data['selected_services']));
                $array2 = array_diff(explode(',', $data['selected_services']),explode(',', $request['selected_services']));
                $updated_services = array_merge($array1, $array2);
                if(!empty($updated_services)){
                    $assigned_service_ids = AssignedAdditionalServices::where('check_in_id', $id)->get()->pluck('id')->toArray();
                    if(!empty($assigned_service_ids)){
                        AssignedBillingTimeRules::whereIn('property_service_id', $assigned_service_ids)->delete();
                        AssignedAdditionalServices::where('check_in_id', $id)->delete();
                    }
                    $selected_services = explode(",", $data['selected_services']);
                    sort($selected_services);
                    foreach ($selected_services as $s_services) {
                        $additional_services = PropertyServices::find($s_services, ['id', 'service_name', 'basis_of_application', 'frequency', 'cost', 'selling_price']);
                        $additional_services['created_by'] = $user->id;
                        $additional_services['check_in_id'] = $id;
                        $additional_services['property_id'] = $request['property_id'];
                        // dd($additional_services);
                        $billing_time_rules = PropertyServiceRules::where('property_service_id', $s_services)->get(['title', 'from', 'to', 'charge_compare_with', 'charge_percentage', 'apply_on'])->toArray();
                        $a_id = AssignedAdditionalServices::create($additional_services);
                        $additional_fields_AS =  ['property_service_id' => $a_id->id, 'created_by' => $user->id];
                        $ABR = Helper::objectsToArray($billing_time_rules, $additional_fields_AS);
                        DB::table('assigned_billing_time_rules')->insert($ABR);
                    }
                }
            }
            unset($data['guestDetails']);
            CheckIn::where('id', $id)->update($data);
            $return = CheckIn::with('guests.roomDetails', 'properties')->where('id', $id)->get();
            DB::commit();
            return Helper::successResponse(CheckInResource::collection($return), 'successfully updated');
        } catch (\Throwable $th) {
            DB::rollback();
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CheckIn $checkIn, $id)
    {
        DB::beginTransaction();
        try {
            if($checkIn->check_in_status == 'checked_out'){
                return Helper::errorResponse('checked out data cannot be deleted');
            }
            $user = JWTAuth::parseToken()->authenticate();
            $checkIn = CheckIn::findOrFail( $id );
            $guests = CheckedInMembers::where('check_in_id', $id)->get()->toArray();
            CheckedInMembers::where('check_in_id', $id)->update([/*'is_deleted' => 1,*/ 'deleted_by' => $user->id]);
            $assigned_services = AssignedAdditionalServices::where('check_in_id', $id)->get()->toArray();
            $assigned_service_ids = collect($assigned_services)->pluck('id')->toArray();
            AssignedAdditionalServices::where('check_in_id', $id)->update([/**'is_deleted' => 1, */ 'deleted_by' => $user->id]);
            AssignedBillingTimeRules::whereIn('property_service_id', $assigned_service_ids)->update([/**'is_deleted' => 1, */ 'deleted_by' => $user->id]);
            $room_ids = collect($guests)->pluck('room_number')->unique()->toArray();
            RoomList::whereIn('id', $room_ids)->update(['room_status' => 'available', 'updated_by' => $user->id, 'check_in_date' => null, 'check_out_date' => null]);
            // Set the is_deleted field to 1
            // $checkIn->is_deleted = 1;
            $checkIn->deleted_by = $user->id;
            $checkIn->save();
            $checkIn->delete();
            // CheckIn::where( 'check_ins_id', $id )->delete();
            DB::commit();
            return Helper::successResponse('Successfully Deleted', 200);
            // return Helper::successResponse(response()->noContent());
        } catch (\Throwable $th) {
            DB::rollBack();
            return Helper::errorResponse($th->getMessage());
        }
    }


    //function for partial checkout
    public function checkin_from_existing_data($check_in_array, $CHECK_OUT_DATE, $CHECK_OUT_TIME, $user)
    {
        DB::beginTransaction();
        try {
            date_default_timezone_set("Asia/Karachi");
            foreach ($check_in_array as $data) {
                $guest_to_exclude = $data['guests'];
                $billing_rules = AssignedAdditionalServices::with(['billingRules' => function ($query) {
                    $query->whereIn('charge_compare_with', ['Checkin_Checkout_Time', 'Checkin Time', 'Checkout Time']);
                }])->where('check_in_id', $data['check_in_id'])->get()->pluck('billingRules')->toArray();
                $time_to_compare = Carbon::parse($CHECK_OUT_TIME);
                foreach ($billing_rules[0] as $rule) {
                    $from = Carbon::parse($rule['from']);
                    $to = Carbon::parse($rule['to']);
                    if ($time_to_compare->between($from, $to) && $data['guests'] !== null) {
                        $CHECK_OUT_TIME = $to->addMinute(1);
                    }
                }
                // dd($CHECK_OUT_TIME);
                if ($guest_to_exclude === null) {
                    DB::commit();
                    continue;
                }
                $remaining_guest_records = CheckedInMembers::select()->where('check_in_id', $data['check_in_id'])->whereNotIn('id', $guest_to_exclude)->get()->toArray();
                $remaining_guest_records = Helper::unsetFields($remaining_guest_records);
                $number_of_remaining_guests = count($remaining_guest_records);
                if ($number_of_remaining_guests === 0) {
                    DB::commit();
                    continue;
                }
                $check_in_data = CheckIn::where('id', $data['check_in_id'])->get()->toArray();
                $property_id = $check_in_data[0]['property_id'];
                $check_in_data[0]['total_persons'] = $number_of_remaining_guests;
                $check_in_data[0]['last_check_in_id'] = $data['check_in_id'];
                $check_in_data[0]['check_in_date'] = $CHECK_OUT_DATE;
                $check_in_data[0]['check_in_time'] = $CHECK_OUT_TIME;
                if($check_in_data[0]['parent_id'] == 0){
                    $check_in_data[0]['parent_id'] = $data['check_in_id'];
                }
                $check_in_data[0]['guestDetails'] = $remaining_guest_records;

                //function to shape the payload according to checkin function
                $checkin_payload = $this->checkInPayload($check_in_data);

                //call checkin function to checkin data
                $response = $this->checkIn($checkin_payload[0], 're_checkin', $data['check_in_id']);
                if (isset($response['error'])) {
                    return $response['error'];
                }
                //calculate missing charges if room is fully checkout and chargeable according to rule
                $new_rooms = collect($remaining_guest_records)->pluck('room_number')->unique()->toArray();
                $old_rooms = CheckedInMembers::where('check_in_id', $data['check_in_id'])->get()->toArray();
                $old_rooms = collect($old_rooms)->pluck('room_number')->unique()->toArray();
                $missing_charges = $this->missingCharges($old_rooms, $new_rooms, $CHECK_OUT_DATE, $CHECK_OUT_TIME, $property_id, $data['check_in_id'], $user);
                if ($missing_charges !== "OK") {
                    return $missing_charges;
                }
            }
            DB::commit();
            return "OK";
        } catch (\Throwable $th) {
            DB::rollback();
            return $th->getMessage();
        }
    }

    //function to relocate guests
    public function relocate($check_in_array, $CHECK_OUT_DATE, $CHECK_OUT_TIME, $user)
    {
        try {
            date_default_timezone_set("Asia/Karachi");
            foreach ($check_in_array as $data) {
                // get assigned additional services with assignes rules of current checkin
                $billing_rules = AssignedAdditionalServices::with(['billingRules' => function ($query) {
                    $query->whereIn('charge_compare_with', ['Checkin_Checkout_Time', 'Checkin Time', 'Checkout Time']);
                }])->where('check_in_id', $data['check_in_id'])->get()->pluck('billingRules')->flatten()->toArray();
                //compare the checkout time with billing rules, if its between any service time then next checkin time will be the end time of that service in case of relocation
                $time_to_compare = Carbon::parse($CHECK_OUT_TIME);
                // $test_obj = [];
                // return $billing_rules;
                foreach ($billing_rules as $rule) {
                    $from = Carbon::parse($rule['from']);
                    $to = Carbon::parse($rule['to']);
                   
                    // $test_obj['rules'][] = $rule['from']."-".$rule['to'];
                    if ($time_to_compare->between($from, $to) && $data['guests'] != null) {
                        $CHECK_OUT_TIME = $to->addMinute(1);
                    }
                }
                // return $CHECK_OUT_TIME->format('M, d Y H:i:s A');
                //guest data to relocate
                $guest_data = $data['guests'];
                $relocate_check_in_data = null;
                //group the relocate guests data and group them by checkin id, as people relocated in same property will have on checkin id
                $group_data = collect($guest_data)->groupBy('property_id')->toArray();
                $relocated_rooms = collect($guest_data)->pluck('room_number')->unique()->toArray();
                $guest_to_exclude = collect($guest_data)->pluck('guest_id')->toArray();

                //get all the guests of current checkin
                $guest_records = CheckedInMembers::select()->where('check_in_id', $data['check_in_id'])->get()->toArray();

                //remaining guests that are not relocated
                $remaining_guest_records = collect($guest_records)->whereNotIn('id', $guest_to_exclude)->all();
                $number_of_remaining_guests = count($remaining_guest_records);

                //guests that will be relocated
                $relocated_guest_records = collect($guest_records)->whereIn('id', $guest_to_exclude)->all();
                //get the checkin data of current checkin id ans unset the extra field by helper function
                $check_in_data = CheckIn::where('id', $data['check_in_id'])->get()->toArray();
                $existing_property = $check_in_data[0]['property_id'];
                $check_in_data[0]['check_in_date'] = $CHECK_OUT_DATE;
                $check_in_data[0]['check_in_time'] = $CHECK_OUT_TIME;
                if($check_in_data[0]['parent_id'] == 0){
                    $check_in_data[0]['parent_id'] = $data['check_in_id'];
                }
                $room_numbers = collect($remaining_guest_records)->pluck('room_number')->unique()->toArray();
                $i = 0;
                $remaining_room = $room_numbers;
                $room_numbers = $relocated_rooms;
                //create new checkin records based on the property_id, every property have seperate checkin data
                foreach ($group_data as $index => $g_data) {
                    $relocate_check_in_data[$i] = $check_in_data[0];
                    $relocate_check_in_data[$i]['total_persons'] = count($g_data);
                    $relocate_check_in_data[$i]['selected_services'] = implode(",", collect($g_data)->pluck('selected_services')->first());
                    $relocate_check_in_data[$i]['property_id'] = $index;
                    if ($existing_property === $index) {
                        $relocate_check_in_data[$i] = $check_in_data[0];
                        $relocate_check_in_data[$i]['total_persons'] = count($g_data) + $number_of_remaining_guests;
                        $room_numbers = array_merge($remaining_room, $relocated_rooms);
                    }
                    $i++;
                }
                //check if there any persons left or not relocated, if then create new checkin for them
                $existing_property_exist = collect($guest_data)->contains('property_id', $existing_property);
                if ($number_of_remaining_guests > 0 && $existing_property_exist === false) {
                    $check_in_data[0]['last_check_in_id'] = $data['check_in_id'];
                    $relocate_check_in_data[$i] = $check_in_data[0];
                    $relocate_check_in_data[$i]['total_persons'] = count($g_data);
                    // $room_numbers = array_merge($remaining_room, $relocated_rooms);
                }
                // remove checkin records where no guest exists and inserts in database
                $filter_check_in_data = array_filter($relocate_check_in_data, function ($record) {
                    return $record['total_persons'] > 0;
                });
                $checkin_payload = [];
                $key = true;
                foreach ($filter_check_in_data as $c_data) {
                    foreach ($guest_records as $guest) {
                        foreach ($guest_data as $value) {
                            if ($guest['id'] === $value['guest_id'] && $c_data['property_id'] === $value['property_id']) {
                                $guest['room_number'] = $value['room_number'];
                                $c_data['guestDetails'][] = $guest;
                            }
                        }
                    }
                    if (!empty($remaining_guest_records) && $c_data['property_id'] === $existing_property && $key === true) {
                        if (isset($c_data['guestDetails'])) {
                            foreach ($remaining_guest_records as $record) {
                                $c_data['guestDetails'][] = $record;
                            }
                        } else {
                            $c_data['guestDetails'] = $remaining_guest_records;
                        }
                        $key = false;
                    }
                    if (isset($c_data['guestDetails'])) {
                        $checkin_payload[] = $c_data;
                    }
                }
                $checkin_payload = $this->checkInPayload($checkin_payload);
                if (isset($checkin_payload['error'])) {
                    return $checkin_payload['error'];
                } else {
                    foreach ($checkin_payload as $payload) {
                        if($payload['property_id'] === $existing_property){
                            $response = $this->checkIn($payload, 're_checkin', $data['check_in_id']);
                        } else{
                            $response = $this->checkIn($payload);
                        }
                        // check if there is any error while checkin
                        if (isset($response['error'])) {
                            return $response['error'];
                        }
                    }
                }
            }
            return "OK";
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    //shape payload according checkin finction
    public function checkInPayload($checkin)
    {
        try {
            $payload = [];
            foreach ($checkin as $data) {
                $array = [];
                $array[] = $data;
                $array = Helper::unsetFields($array);
                $data = $array[0];
                unset($data['created_by']);
                unset($data['last_check_in_id']);
                unset($data['check_in_status']);
                // dd($data);
                $fieldsToRemove = ['id', 'check_in_id', 'property_id', 'created_by'];
                $data['guestDetails'] = Helper::unsetFields($data['guestDetails']);
                $data['guestDetails'] = array_map(function ($record) use ($fieldsToRemove) {
                    return collect($record)->except($fieldsToRemove)->all();
                }, $data['guestDetails']);
                $payload[] = $data;
            }
            return $payload;
        } catch (Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    public function missingCharges($old_rooms, $new_rooms, $CHECK_OUT_DATE, $CHECK_OUT_TIME, $property_id, $check_in_id, $user)
    {
        try {
            $difference = $difference = array_diff_key($old_rooms, $new_rooms);
            if ($difference === null) {
                return "OK";
            }
            $charge_time = PropertySetting::where('property_id', $property_id)->where('key', 'room_charge_time')->first('value')->toArray();
            $charge_time = date("H:i:s", strtotime($charge_time['value']));
            $CHECK_OUT_TIME = date("H:i:s", strtotime($CHECK_OUT_TIME));
            if ($CHECK_OUT_TIME <= $charge_time) {
                return "OK";
            }
            foreach ($difference as $room) {
                $data = [];
                $room_type = RoomList::find($room, ['room_type_id', 'room_number'])->toArray();
                $room_pricing = RoomType::find($room_type['room_type_id'], ['room_type', 'hiring_cost', 'rental_price'])->toArray();
                $data['check_in_id'] = $check_in_id;
                $data['property_id'] = $property_id;
                $data['assigned_additional_service_id'] = 0;
                $data['cost'] = $room_pricing['hiring_cost'];
                $data['created_by'] = $user->id;
                $data['quantity'] = 1;
                $data['uom'] = 'days';
                $data['days'] = 1;
                $data['total_amount'] = $room_pricing['hiring_cost'];
                $data['item_name'] = "Missing room charges for " . $room_pricing['room_type'] . " on " . $CHECK_OUT_DATE . " in partial checkout";
                Payable::create($data);
                unset($data['cost']);
                $data['selling_price'] = $room_pricing['rental_price'];
                $data['total_amount'] = $room_pricing['rental_price'];
                PropertyBilling::create($data);
            }
            return "OK";
        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }

    //function to update room for guests
    public function updateGuestRooms(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $previous_rooms = CheckedInMembers::where('check_in_id', $request->check_in_id)->get()->pluck('room_number')->toArray();
            foreach ($request->guests as $guest) {
                CheckedInMembers::where('id', $guest['guest_id'])->update(['room_number' => $guest['room_number'], 'updated_by' => $user->id]);
            }
            $updated_rooms = CheckedInMembers::where('check_in_id', $request->check_in_id)->get()->pluck('room_number')->toArray();
            $checked_out_rooms = collect($previous_rooms)->diff($updated_rooms)->unique()->toArray();
            RoomList::whereIn('id', $checked_out_rooms)->update(['room_status' => 'available', 'check_in_date' => null, 'check_out_date' => null, 'updated_by' => $user->id]);
            DB::commit();
            return Helper::successResponse([], 'successfully updated');
        } catch (Throwable $th) {
            DB::rollBack();
            return Helper::errorResponse($th->getMessage());
        }
    }


    //get checkin data by bound country
    public function countryCheckin(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $company_id = $user->company_id;
            if (isset($request->bound_country)) {
                $query = CheckIn::with(['guests', 'property'])->where('check_in_status', 'active')->where('bound_country', $request->bound_country)
                    ->whereHas('property', function ($q) use ($company_id) {
                        $q->where('company_id', '=', $company_id);
                    })->get();
                if (empty($query->toArray())) {
                    return Helper::errorResponse('no active checkins found against ' . $request->bound_country);
                }
                return Helper::successResponse(CheckInResource::collection($query));
            } else {
                $query = CheckIn::with(['guests', 'property'])->where('check_in_status', 'active')
                    ->whereHas('property', function ($q) use ($company_id) {
                        $q->where('company_id', '=', $company_id);
                    })->get();
                if (empty($query->toArray())) {
                    return Helper::errorResponse('no active checkins found against');
                }
                return Helper::successResponse(CheckInResource::collection($query));
            }
        } catch (Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }
    //delete bulk checkins
    public function deleteCheckins(Request $request){
        // dd($request->toArray());
        DB::beginTransaction();
        try {

            if(!isset($request->checkin_ids)){
                return Helper::errorResponse('select valid checkins to delete');
            }
            $user = JWTAuth::parseToken()->authenticate();
            $checkins = checkIn::whereIn('id', $request->checkin_ids)->get()->toArray();
            $checked_out_ids = collect($checkins)->where('check_in_status', 'checked_out')->pluck('id')->toArray();
            if(!empty($checked_out_ids)){
                return Helper::errorResponse('checked out data cannot be deleted');
            }
            $active_rooms = CheckedInMembers::whereIn('check_in_id', $request->checkin_ids)->get()->pluck('room_number')->unique()->toArray();
            CheckedInMembers::whereIn('check_in_id', $request->checkin_ids)->update([/**'is_deleted' => 1, */ 'deleted_by' => $user->id]);
            $assigned_services = AssignedAdditionalServices::whereIn('check_in_id', $request->checkin_ids)->get()->toArray();
            $assigned_service_ids = collect($assigned_services)->pluck('id')->toArray();
            AssignedAdditionalServices::whereIn('check_in_id', $request->checkin_ids)->update([/**'is_deleted' => 1, */ 'deleted_by' => $user->id]);
            AssignedBillingTimeRules::whereIn('property_service_id', $assigned_service_ids)->update([/**'is_deleted' => 1, */ 'deleted_by' => $user->id]);
            if(!empty($active_rooms)){
                RoomList::whereIn('id', $active_rooms)->update(['room_status' => 'available', 'updated_by' => $user->id, 'check_in_date' => null, 'check_out_date' => null]);
            }
            checkIn::whereIn('id', $request->checkin_ids)->update([/**'is_deleted' => 1, */ 'deleted_by' => $user->id]);
            DB::commit();
            return Helper::successResponse('Successfully Deleted', 200);
            // return Helper::successResponse(response()->noContent());
        } catch (\Throwable $th) {
            DB::rollBack();
            return Helper::errorResponse($th->getMessage());
        }
    }


    //function to add services for checkins
    public static function addServices($checkIn, $checkin_type, $previous_checkin_id, $checkInId, $user){
        DB::beginTransaction();
        try{
            if($checkin_type == 're_checkin' && $previous_checkin_id != null){
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
