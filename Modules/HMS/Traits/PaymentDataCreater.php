<?php

namespace Modules\HMS\Traits;

use Modules\HMS\App\Models\AssignedBillingTimeRules;
use Modules\HMS\App\Models\CheckedInMembers;
use Modules\HMS\App\Models\CheckIn;
use Modules\HMS\App\Models\PropertyServices;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Carbon;
use Modules\HMS\App\Utilities\Helper;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use function Psy\debug;

trait PaymentDataCreater
{

    private $debug = [];
    private $payable = [];
    private $billing = [];
    private $isDebug = false;
    private $current_check_in_id; //Store current check_in_id from array of @$check_in_ids 
    private $check_in_ids = []; //Store check_in_ids to process check_outs
    private $default_values = [];
    private $recheck_ins = [];

    private $default_fields = ['check_in_id', 'property_id', 'created_by'];
    private $required_fields = [
        'quantity', 'days', 'assigned_additional_service_id', 'item_name',
        'selling_price', 'uom', 'cost'
    ];
    private $time_rules = ['checkin_checkout_time', 'checkin time', 'checkout time'];
    private $errors = [];
    private $CHECKOUT_DATE = null;
    private $CHECKOUT_TIME = null;
    private $CHECKIN_DATE = null;
    private $CHECKIN_TIME = null;
    private $user;

    private $check_in_data;
    private $current_check_in_data;
    private $current_guests_array;
    private $guestAndRoomData;
    private $current_guest_and_room_data;
    private $overallRoomIds;

    /**
     * current_check_out_type will be following values for different scenarios
     * @FULL in case of full check_out, 
     * @RE-CHECKIN in case of relocation and partial check_out,
     * @BILLING in case of generate missing bills
    */
    private $current_check_out_type;
    private $check_out_array;

    private $service_stay_duration;
    private $room_stay_duration;
    private $test = [];

    private $calculate_age_bases = 'checkout'; #TODO: option need get from relevant property Settings

    /**
     * Add a record to the data array.
     *
     * @param array $record
     * @return void
     */

    public function process_checkout($request, $check_out_array = [], $createReCheckins = false, $debug = false)
    {

        $input  = ($request instanceof Request) ? $request->all() : $request;

        $validator = Validator::make($input, [
            'check_in_data' => 'required|array',
            'check_in_data.*.check_in_id' => 'required|integer',
            'check_in_data.*.guests' => 'nullable|array',
            // 'check_in_data.*.check_out_type' => 'required|string',
        ]);




        if ($validator->fails()) {
            throw new \Exception($validator->errors());
        }

        $check_in_records = collect($input['check_in_data']);
        $this->check_in_ids = $check_in_records->pluck('check_in_id')->toArray();

        if ($debug) $this->isDebug = true;

        $this->CHECKOUT_TIME = (!isset($input['check_out_time']) || empty($input['check_out_time'])) ? Carbon::now()->toTimeString() : $this->convertToTime($input['check_out_time']);
        $this->CHECKOUT_DATE = (!isset($input['check_out_date']) || empty($input['check_out_date'])) ? Carbon::now()->toDateString() : $input['check_out_date'];

        $this->validateCheckInRecords();
        $this->loadCheckInData();
        $this->loadGuestAndRoomData();


        foreach ($check_in_records->toArray() as $records) {

            $this->current_check_in_id = $records['check_in_id'];
            //by default current check_out type is full, will be updated if re-check-ins created
            $this->current_check_out_type = (isset($records['check_out_type'])) ? $records['check_out_type'] : 'full'; //$check_in_records->where('check_in_id', $check_in_id)->pluck('check_out_type')->first();
            $this->current_guests_array = $records['guests'];

            if (count($check_out_array) > 0 && (count($this->check_in_ids) === count($check_out_array))) {
                $this->CHECKOUT_DATE = $check_out_array[$this->current_check_in_id]['checkout_date'];
                $this->CHECKOUT_TIME = $check_out_array[$this->current_check_in_id]['checkout_time'];
            }

            $this->setCurrentCheckInData($this->current_check_in_id);
            if ($createReCheckins) $this->createReCheckins($createReCheckins);


            $this->applyRoomCharges();
            $this->applyAdditionalServices();
            $this->applyRules();

            $this->updateCheckInStatus($this->current_check_in_id, $this->current_check_out_type, $this->CHECKOUT_DATE, $this->CHECKOUT_TIME, $this->user->id);

        }


        $payable = collect($this->payable)->sortBy(['check_in_id', 'assigned_additional_service_id'])->toArray();
        $billing = collect($this->billing)->sortBy(['check_in_id', 'assigned_additional_service_id'])->toArray();
        // update Room Status
        DB::table('room_lists')->whereIn('id',  $this->overallRoomIds)->where('room_status', 'occupied')->update(['room_status' => 'available', 'updated_by' => $this->user->id, 'check_in_date' => null, 'check_out_date' => null]);
        DB::table('property_billings')->insert($billing);
        DB::table('payables')->insert($payable);
    }


    /**
     * Check if check_in_id is valid array
     * Set check_in_data with check_in_data, additionlal services and their billing rules of given check_in_ids
     */
    public function loadCheckInData()
    {
        $this->isCheckInIdsAssigned(); // function to check if check_in_id is valid array
        $this->check_in_data = CheckIn::with(['additionalServices', 'additionalServices.billingRules'])->whereIn('id', $this->check_in_ids)->get()->toArray();
        // dd($this->check_in_data);
    }

    /**
     * Set and Apply ROOM CHARGES for current check_in/family/id 
     */
    public function applyRoomCharges()
    {
        //charge the current day i checkout the same day before day charge time, as 1st day will be charged in any case
        // $room_duration = ($this->current_check_out_type == 'full' && $this->room_stay_duration == 0) ? $this->room_stay_duration + 1 : $this->room_stay_duration;
        $rooms = $this->current_guest_and_room_data['room_rent'];
        if (is_array($rooms)) {
            foreach ($rooms as $room) {
                $this->addRow([
                    'days' => $this->room_stay_duration,
                    'assigned_additional_service_id' => 0,
                    'quantity' => $room['count'],
                    'cost' => $room['Hiring_cost'],
                    'selling_price' => $room['Selling_price'],
                    // 'total_amount_cost' => $room['Hiring_cost'] * $this->room_stay_duration * $room['count'],
                    // 'total_amount_selling' => $room['Selling_price'] * $this->room_stay_duration * $room['count'],
                    'uom' => 'days',
                    'item_name' => "Room Rent For " . $room['Room_type'],
                ]);
            }
        }
    }


    /**
     * Set and Apply ADDITIONAL SERVICE CHARGES for current check_in/family/id
     */
    public function applyAdditionalServices()
    {
        $additionalServices = $this->current_check_in_data['additional_services'];

        if (is_array($additionalServices)) {

            foreach ($additionalServices as $additionalService) {

                $bases_of_application = strtolower($additionalService['basis_of_application']);
                $frequency = strtolower($additionalService['frequency']);
                $uom = 'days';
                $quantity = 1;
                $days = $this->service_stay_duration;
                $additional_addRow_option = [];

                if ($bases_of_application === 'guests') {
                    $uom = 'No Of Guests';
                    $quantity = $this->current_guest_and_room_data['guest_count'];
                    if (strtolower($this->current_check_in_data['check_in_type']) === 'event')   $quantity = $this->current_check_in_data['total_persons'];
                }

                if ($bases_of_application === 'family') {
                    $uom = 'Whole Group';
                    $quantity = 1;
                }

                if ($bases_of_application === 'room') {
                    $uom = 'Number Of Rooms';
                    $quantity = $this->current_guest_and_room_data['total_rooms'];
                }

                if ($frequency === 'one time') {
                    $days = 0;
                    $additional_addRow_option = ['charge_type'=>'one_time', 'cost' => $additionalService['cost'], 'selling' => $additionalService['selling_price']];
                }

                $this->addRow([
                    'days' => $days,
                    'assigned_additional_service_id' => $additionalService['id'],
                    'quantity' => $quantity,
                    'cost' => $additionalService['cost'],
                    'selling_price' => $additionalService['selling_price'],
                    'uom' => $uom,
                    'item_name' => $additionalService['service_name'],
                ],$additional_addRow_option);
            }
        }
    }



    /**
     * Check all available billing rules for services and rooms
     * Set and Apply BILLING RULES ON ROOM CHARGES AND ADDITIONAL SERVICE CHARGES
     */
    public function applyRules()
    {
        $additionalServices = $this->current_check_in_data['additional_services'];


        if (!is_array($additionalServices)) return;

        foreach ($additionalServices as $additionalService) {

            if (!isset($additionalService['billing_rules']) || !is_array($additionalService['billing_rules'])) continue;
            // return collect($this->current_guest_and_room_data['guest_age'])->groupBy('room_number');


            $service_id = $additionalService['id'];
            $service_name = $additionalService['service_name'];
            $service_selling_price = $additionalService['selling_price'];
            $service_cost = $additionalService['cost'];



            $payable_total_amount = collect($this->payable)->where('assigned_additional_service_id', $service_id)->pluck('total_amount')->first();
            $billing_total_amount = collect($this->billing)->where('assigned_additional_service_id', $service_id)->pluck('total_amount')->first();


            $guest_rules = $this->getGuestRules($additionalService['billing_rules']); //function to find billing rules for guest age
            $guest_count = $this->current_guest_and_room_data['guest_count'];


            $service_time_data = collect($additionalService['billing_rules'])
            ->map(function ($rules) {
                return collect($rules)->map(function ($rule) {
                    return strtolower($rule);
                })->all();
            })->whereIn('charge_compare_with', $this->time_rules)->first();

            foreach ($additionalService['billing_rules'] as $rules) {

                $charge_type = strtolower($rules['charge_compare_with']);
                $isTime = (in_array($charge_type, $this->time_rules)) ? true : false;

                $from = ($isTime) ? $this->convertToTime($rules['from']) : $rules['from'];
                $to = ($isTime) ? $this->convertToTime($rules['to']) : $rules['to'];

                $applyon = $rules['apply_on'];
                $chargePercentage = $rules['charge_percentage'];
                $chargeMultiplier = (100 - $chargePercentage) / 100;
                // service cost
                $options = ['cost' => $payable_total_amount, 'selling'  => $billing_total_amount];

                if ($isTime) {

                    $costAndsellingDiscounts = $this->getChargeAbleGuestsDiscount($guest_rules, $service_selling_price, $service_cost); //function add discount for guest on basis of age
                    $time_basis = '';
                    $row = function () use(&$time_basis, $service_id, $guest_count, $costAndsellingDiscounts, $service_name, $charge_type, $service_selling_price, $service_cost){
                        return  [
                            'days' => -1,
                            'assigned_additional_service_id' => $service_id,
                            'quantity' => $guest_count,
                            'cost' => $costAndsellingDiscounts['disc_on_cost'] / $guest_count,
                            'selling_price' => $costAndsellingDiscounts['disc_on_selling'] / $guest_count,
                            'uom' => "No of Guests",
                            'item_name' =>  $service_name . " Skipped due to " . $charge_type . " Based ON ".$time_basis." With Selling Price of " . $service_selling_price . " and Cost of " . $service_cost,
                        ];
                    };
                    if ($charge_type === 'checkin_checkout_time') {
                        if ($this->CHECKIN_TIME > $to) {
                            $time_basis = 'Checkin Time';
                            $this->addRow($row(), $options, $applyon);
                        }
                        if ($this->CHECKOUT_TIME <= $from) {
                            $time_basis = 'Checkout Time';
                            $this->addRow($row(), $options, $applyon);
                        }
                        
                    }
                    if ($charge_type === 'checkin time') {
                        if ($this->CHECKIN_TIME > $to){
                            $time_basis = 'Checkin Time';
                            $this->addRow($row(), $options, $applyon);
                        }
                    }
                    
                    if ($charge_type === 'checkout time') {
                        if ($this->CHECKOUT_TIME <= $from) {
                    }
                        $time_basis = 'Checkout Time';
                        $this->addRow($row(), $options, $applyon);
                    }                    
                }

                if ($charge_type === 'guest age') {


                    $guests_ages = $this->current_guest_and_room_data['guest_age'];

                    foreach ($guests_ages as $guest_age) {

                        if ($guest_age['age'] >= $from && $guest_age['age'] <= $to) {

                            $discount_cost =  $chargeMultiplier * $service_cost;
                            $discount_selling_price = $chargeMultiplier * $service_selling_price;

                            $this->addRow([
                                'days' => $this->service_stay_duration,
                                'assigned_additional_service_id' => $service_id,
                                'quantity' => -1,
                                'cost' => $discount_cost,
                                'selling_price' => $discount_selling_price,
                                'uom' => 'No of Guests',
                                'item_name' => $service_name . " Discounts on " . $rules['title'] . " through Guest Age " . $guest_age['age']
                            ], $options, $applyon);
                        }
                    }
                }

                if ($charge_type === 'number of guest in rooms') {
                    #todo: need to add validation that This charge type can only be allowed once against one service;
                    $min_max_guests_age_array = $this->getMinMaxAgesOfChargeableGuests($additionalService['billing_rules'], 100); //function to find minimum and maximum ages which are eligible for discounts from billing rules
                    $rooms_wise_guests = collect($this->current_guest_and_room_data['guest_age'])->groupBy('room_number');
                    $max_count_in_rule = $rules['to'];

                    if ($max_count_in_rule <= 0) continue;

                    foreach ($rooms_wise_guests as $room_number => $room_guests) {

                        $chargeAbleGuestsInRoom = collect($room_guests)->whereBetween('age', $min_max_guests_age_array)->count();
                        $number_of_discounted_guests = ($chargeAbleGuestsInRoom > $max_count_in_rule) ? $max_count_in_rule : $chargeAbleGuestsInRoom;
                        $quantity = $number_of_discounted_guests;
                        $days = $this->service_stay_duration;

                        //handle if there is no rules exist for service time
                        if($service_time_data != null){
                            $service_from = $this->convertToTime($service_time_data['from']);
                            $service_to = $this->convertToTime($service_time_data['to']);
    
                            if ($service_time_data['charge_compare_with'] === 'checkin_checkout_time') {
    
                                if ($this->CHECKIN_TIME > $service_to) $days--;
                                if ($this->CHECKOUT_TIME <= $service_from) $days--;
                            }
    
                            if ($service_time_data['charge_compare_with'] === 'checkin time') {
                                if ($this->CHECKIN_TIME > $service_to) $days--;
                            }
    
                            if ($service_time_data['charge_compare_with'] === 'checkout time') {
                                if ($this->CHECKOUT_TIME <= $service_from)  $days--;
                            }
                        }
                        if($days <= 0) continue;
                        $this->addRow([
                            'days' => -$days,
                            'assigned_additional_service_id' => $service_id,
                            'quantity' => $quantity,
                            'cost' => $chargeMultiplier * $service_cost,
                            'selling_price' => $chargeMultiplier * $service_selling_price,
                            'uom' => 'No of Services',
                            'item_name' => $service_name . " Discount due to " . $rules['title'] . " on " . $charge_type . " with Room_Number " . $room_number
                        ]);
                    }
                }
            }
        }
    }


    /**
     * Add DISCOUNTS for guests on basis of age
     */
    private function getChargeAbleGuestsDiscount($guestAgeRules, $selling_price, $cost)
    {
        $guest_data = $this->current_guest_and_room_data;

        $guestAges = $guest_data['guest_age'];

        if (!is_array($guestAges) || !is_array($guestAgeRules)) {
            throw new \Exception("Guest Ages & GuestAgeRules must be an array");
        }
        $discount_on_selling = 0;
        $discount_on_cost = 0;

        foreach ($guestAgeRules as $rule) {

            foreach ($guestAges as $age) {
                $is_age_matched = (($age['age'] > $rule['from'] || $age['age'] + 1 > $rule['from']) && $age['age'] <= $rule['to']);

                if ($is_age_matched) {
                    if ($rule['apply_on'] == 'selling') {
                        $discount_on_selling += ($rule['charge_percentage'] * $selling_price) / 100;
                        $discount_on_cost +=  $cost;
                    } else if ($rule['apply_on'] == 'cost') {
                        $discount_on_selling +=  $selling_price;
                        $discount_on_cost += ($rule['charge_percentage'] * $cost) / 100;
                    } else {
                        $discount_on_selling += ($rule['charge_percentage'] * $selling_price) / 100;
                        $discount_on_cost += ($rule['charge_percentage'] * $cost) / 100;
                    }
                }
            }
        }
        return ['disc_on_selling' => $discount_on_selling, 'disc_on_cost' => $discount_on_cost];
    }


    /**
     * Get all billing_rules for GUEST AGE
     * Get the MINIMUM AND MAXIMUM AGE eligible for discount
     * return MINIMUM AND MAXIMUM AGE
     */
    public function getMinMaxAgesOfChargeableGuests($current_billing_rules, $charge_percentage)
    {
        $chargeableRules = $this->getGuestRules($current_billing_rules, $charge_percentage); //function to get billing rules for guest age
        if (is_array($chargeableRules) && count($chargeableRules) > 0) {
            $charge_able_rules = collect($chargeableRules);
            $min_age_from = $charge_able_rules->pluck('from')->min();
            $max_age_to = $charge_able_rules->pluck('to')->max();
            return [$min_age_from, $max_age_to];
        }
        return [];
    }

    /**
     * Set data for current_check_in_data with current check_in/family/id
     * Set data for current_guest_and_room_data with guest and their room details of current check_in/family/id
     * Set data for default_values for current check_in/family/id
     * Set data for CHECKIN_DATE, CHECKIN_TIME and CHECKOUT_DATE of current check_in/family/id
     * Set stay duration of current check_in/family/id
     */
    private function setCurrentCheckInData($check_in_id)
    {
        #todo: check if check_in_data exists
        $this->current_check_in_data =  collect($this->check_in_data)->where('id', $check_in_id)->first();
        $this->current_guest_and_room_data = (isset($this->guestAndRoomData[$check_in_id])) ? $this->guestAndRoomData[$check_in_id] : [];
        // set dafault value for current check in data
        $this->default_values = array_intersect_key($this->current_check_in_data, array_flip($this->default_fields));
        $this->user =  JWTAuth::parseToken()->authenticate();
        $this->default_values['check_in_id'] = $check_in_id;
        $this->default_values['created_by'] = $this->user->id;
        // set dafault value for current check in data
        $this->CHECKIN_DATE = Carbon::parse($this->current_check_in_data['check_in_date']);
        $this->CHECKIN_TIME = $this->convertToTime($this->current_check_in_data['check_in_time']);
        // convert Checkout date in Carbon Object
        $this->CHECKOUT_DATE = Carbon::parse($this->CHECKOUT_DATE);

        $this->setStayDuration(); //function to set stay duration for current check_in/family/id
    }


    /**
     * Add and set the billing data of current check_in/family/id
     */
    public function addRow(array $records, $options = [], $type = 'both')
    {
        $this->prepareRowRecord($records, $type, $options);
    }


    public function setDefaultValues()
    {

        $this->default_values = array_intersect_key($this->current_check_in_data, $this->default_fields);
    }

    /**
     * Validate to check if all required fields are availale by comparing the array
     */
    private function validateRecords(array $array, array $compare_with, $type)
    {

        $missingFields = array_diff($compare_with, array_keys($array));

        if (!empty($missingFields)) {
            throw new \Exception(implode(',', $missingFields) . " are not Available in " . $type . " " . implode(',', $compare_with));
        }
        return $array;
    }

    /**
     * Validate the record to check if all required fields are available
     * Add billing data in payable and billing array of current check_in/family/id
     * Add billing data in debug array of current check_in/family/id to re-check in case of error
     */
    private function prepareRowRecord(array $rowRecords, $type, $options)
    {
        $required_fields = $this->validateRecords($rowRecords, $this->required_fields, "Required Fields");
        $single_object =  array_merge($this->default_values, $required_fields);
        $type = strtolower($type);

        $convert_day_zero_to_one = (isset($options['charge_type']) && $options['charge_type'] === 'one_time')?true:false;
        if (empty($options) || isset($options['cost']) && $options['cost'] > 0) {

            if ($type === 'both' || $type === 'cost') {
                $payable = $single_object;
                unset($payable['selling_price']);

                if (isset($payable['total_amount_cost'])) {
                    $payable['total_amount'] = $payable['total_amount_cost'];
                    unset($payable['total_amount_cost']);
                    unset($payable['total_amount_selling']);
                } else {
                    $days = ($payable['days'] === 0 && $convert_day_zero_to_one ) ? 1 : $payable['days'];
                    $payable['total_amount'] = $payable['quantity'] * $days * $payable['cost'];
                }
                if ($payable['cost'] > 0) {
                    $this->payable[] = $payable;
                    $this->generateDebug('payable', $payable); //function to set data of current check_in/family/id in case of error
                }
            }
        }
        if (empty($options) || isset($options['selling']) && $options['selling'] > 0) {
            if ($type === 'both' || $type === 'selling') {
                $billing = $single_object;

                unset($billing['cost']);
                if (isset($billing['total_amount_selling'])) {
                    $billing['total_amount'] = $billing['total_amount_selling'];
                    unset($billing['total_amount_selling']);
                    unset($billing['total_amount_cost']);
                } else {
                    $days = ($billing['days'] === 0 && $convert_day_zero_to_one) ? 1 : $billing['days'];
                    $billing['total_amount'] = $billing['quantity'] * $days * $billing['selling_price'];
                }
                if ($billing['selling_price'] > 0) {
                    $this->billing[] = $billing;
                    $this->generateDebug('billing', $billing); //function to set data of current check_in/family/id in case of error
                }
            }
        }
    }

    public function resetData()
    {
        $this->payable = [];
        $this->billing = [];
    }



    /**
     * get records from check_ins table for given check_in_ids 
     *
     * @return checkin data of given ids
     */
    public function getCheckinRecords()
    {
        return  CheckIn::find($this->check_in_ids)->toArray();
    }




    /**
     * Apply some validations for given ids before continue the checkout process
     * Get records from check_ins table of given ids 
     * Check the check_in_status of given ids
     * Apply Validations for check_out_date and check_out_time
     * @return checkin data of given ids if validation is successful else through error
     */
    public function validateCheckInRecords()
    {

        $check_in_records = $this->getCheckinRecords();
        $isStatusCheckedOut = collect($check_in_records)->where('check_in_status', 'checked_out')->pluck('registeration_number', 'family_name')->toArray();
        if (count($isStatusCheckedOut) > 0) {
            $Checkedout_families = implode(', ', array_map(function ($key, $value) {
                return "$key($value)";
            }, array_keys($isStatusCheckedOut), $isStatusCheckedOut));

            throw new \Exception("Checkout Request Contains Following Families which are already checked out $Checkedout_families");
        }

        $invalid_dateTime = [];
        foreach ($check_in_records as $item) {
            $in_timeDate = $item['check_in_date'] . ' ' . $item['check_in_time'];
            $out_timeDate = $this->CHECKOUT_DATE . ' ' . $this->CHECKOUT_TIME;
            if (strtotime($in_timeDate) > strtotime($out_timeDate)) {
                $rec = [];
                $rec['registeration_number'] = $item['registeration_number'];
                $rec['family_name'] = $item['family_name'];
                $invalid_dateTime[] = $rec;
            }
        }
        if (!empty($invalid_dateTime)) {
            $errors = implode(', ', array_map(function ($record) {
                return $record['registeration_number'] . ' ' . $record['family_name'];
            }, $invalid_dateTime));
            throw new \Exception("Checkout date-time must be after or equal to checkin date-time for '.$errors");
        }
        return $check_in_records;
    }

    /**
     * Get guests data of given check_in_ids 
     * Get room data of all guests
     * Set all guests and their details in guestAndRoomData
     * @return checkedInMembers data of given check_in_ids
     */
    public function loadGuestAndRoomData()
    {
        $this->isCheckInIdsAssigned();
        $ageStartTime = Carbon::parse(($this->calculate_age_bases === 'checkout') ? $this->CHECKOUT_DATE : $this->CHECKIN_DATE);
        $checkInData = CheckedInMembers::with(['rooms', 'rooms.roomtype'])->whereIn('check_in_id', $this->check_in_ids)->get();
        $this->overallRoomIds = collect($checkInData)->pluck('room_number')->unique()->values();
        $members_data = $checkInData->groupBy('check_in_id')->map(function ($group) use ($ageStartTime) {

            $room_ids = collect($group)->pluck('room_number')->unique()->toArray();
            // $count =  $group->pluck('rooms.roomtype.room_type')->all();
            $uniqueRoomTypes = $group->pluck('rooms.roomtype.room_type')->unique()->values()->all();
            $uniqueRoomNumbers = collect($group)->unique('room_number')->toArray();

            $roomTypeWiseData = collect($uniqueRoomTypes)->map(function ($roomType) use ($group, $uniqueRoomNumbers) {
                $count = 0;
                foreach ($uniqueRoomNumbers as  $value) {
                    if ($value['rooms']['roomtype']['room_type'] === $roomType) {
                        $c = collect($uniqueRoomNumbers)->filter(function ($record) use ($roomType) {
                            return $record['rooms']['roomtype']['room_type'] === $roomType;
                        })->count();
                        $count = $c;
                    }
                }
                $roomData = $group->where('rooms.roomtype.room_type', $roomType)->first();
                return [
                    "Room_type" => $roomType,
                    "count" => $count,
                    "Hiring_cost" => $roomData['rooms']['roomtype']['hiring_cost'],
                    "Selling_price" => $roomData['rooms']['roomtype']['rental_price'],

                ];
            })->values()->all();
            $guestAges = $group->map(function ($guest) use ($ageStartTime) {
                return [
                    "id" => $guest['id'],
                    "guest_name" => $guest['guest_name'],
                    "age" => $ageStartTime->diffInYears($guest['date_of_birth']),
                    "room_number" => $guest['room_number'],
                    "date_of_birth" => $guest['date_of_birth'],
                    "visa_expiry" => $guest['visa_expiry'],
                    "cnic_passport_number" => $guest['cnic_passport_number'],
                    'property_id' => $guest['property_id']
                ];
            })->values()->all();

            return [
                "guest_count" => $group->count('guest_name'),
                "room_rent" => $roomTypeWiseData,
                'guest_age' => $guestAges,
                'room_ids' => $room_ids,
                'total_rooms' => count($room_ids),
            ];
        });

        // return ($check_in_id) ? $members_data[$check_in_id] : $members_data;
        $this->guestAndRoomData = $members_data;
        return $members_data;
    }


    /**
     * Set data for service_stay_duration and room_stay_duration for additional services of current check_in/family/id
     * Set data for daysAndNights(calculate number of days and nights stayed) of current check_in/family/id
     */
    private function setStayDuration()
    {
        $this->service_stay_duration = $this->CHECKOUT_DATE->diffInDays($this->CHECKIN_DATE) + 1;
        $daysAndNights = Helper::calculateDaysNights($this->CHECKIN_DATE, $this->CHECKIN_TIME, $this->CHECKOUT_DATE, $this->CHECKOUT_TIME, $this->current_check_in_data['property_id']);
        //Need ro rectify
        $this->room_stay_duration = $this->current_check_out_type === 'full'
            ? $daysAndNights['stay_duration']
            : ($this->current_check_out_type === 'billing'
                ? $daysAndNights['partial_duration'] + 1
                : $daysAndNights['partial_duration']);
    }

    /**
     * check if is check_in_ids is valid array
     * @return true if valid array
     */
    private function isCheckInIdsAssigned()
    {
        if (is_array($this->check_in_ids) && count($this->check_in_ids) === 0) {
            throw new \Exception("Check In Id is not assigned! Use First Set Check_in_ids from Array");
        }
        return true;
    }


    /**
     * Get all rules for GUEST AGE
     * @return AssignedBillingTimeRules for GUEST AGE
     */
    private function getGuestRules($current_billing_rule, $charge_percentage = null)
    {
        return collect($current_billing_rule)->filter(function ($item) use ($charge_percentage) {
            return ($charge_percentage != null) ?
                strtolower($item['charge_compare_with']) == 'guest age' && strtolower($item['charge_percentage']) == $charge_percentage :
                strtolower($item['charge_compare_with']) == 'guest age';
        })->values()->toArray();
    }


    public function returnArray()
    {
        // Ensure data is not empty before insertion
        return ['payable' => $this->payable, 'billing' => $this->billing];
    }

    public function updateCheckInStatus($check_in_id, $check_in_type, $check_out_date, $check_out_time, $id)
    {
        $present_status = ($check_in_type === 'full') ? 'checked_out' : 'continue';
        CheckIn::where('id', $check_in_id)->update(
            [
                'check_in_status' => 'checked_out', 
                'present_status' => $present_status, 
                'check_out_date' => $check_out_date, 
                'check_out_time' => $check_out_time, 
                'updated_by' => $id
            ]
        );
    }

    /**
     * Convert time to 24 Hour format
     * @return 24 Time in 24 Hour format
     */
    private function convertToTime($timestring)
    {
        return date("H:i:s", strtotime($timestring));
    }

    /**
     * Add billing data in debug array to debug in case of error
     */
    private function generateDebug($type, $record)
    {

        if ($this->isDebug) {

            $this->debug[$this->current_check_in_id][$type][] = $record;
            $this->debug[$this->current_check_in_id]['info'] = [
                'checkin_date_time ' => Carbon::parse($this->CHECKIN_DATE)->format('Y-m-d') . ' ' . Carbon::parse($this->CHECKIN_TIME)->format('h:i A'),
                'checkout_date_time' => Carbon::parse($this->CHECKOUT_DATE)->format('Y-m-d') . ' ' . Carbon::parse($this->CHECKOUT_TIME)->format('h:i A'),
                'check_out_type' => $this->current_check_out_type,
                'stay_duration' => $this->service_stay_duration,
                'room_stay_duration' => $this->room_stay_duration
            ];
            $this->debug[$this->current_check_in_id]['guests_room_data'] = $this->current_guest_and_room_data;
        }
    }
    /**
     * createReCheckins options 
     *
     * @options default is false :
     * @options object  { type : full|partial , } 
     * in case of full check_in_date  & check_in_time is mandatory
     * full means that new checkins will be created for all checkouts
     * partial means that new checkins will be based on check_in_records that either checkin is partial or full
     * @return checkin data
     */
    private function createReCheckins($object)
    {
        if ($object && !is_array($object)) throw new \Exception("Wrong Parameter for ProcessCheckout::createReCheckins");

        $validator = Validator::make($object, [
            'type' => 'required|in:full,partial',
            'check_in_date' => 'required_if:type,full',
            'check_in_time' => 'required_if:type,full'
        ]);

        if ($validator->fails()) {
            throw new \Exception($validator->errors());
        }

        $check_in_type = $object['type'];

        $checkout_date = Carbon::parse($this->CHECKOUT_DATE)->format('Y-m-d');

        $guest_data = $this->current_guest_and_room_data;
        $guest_collection = (isset($guest_data['guest_age']) && count($guest_data['guest_age']) > 0) ? collect($guest_data['guest_age']) : collect([]);
        $guest_ids_in_current_checkin =  $guest_collection->pluck('id')->toArray();

        if ($check_in_type === 'full') {

            $fields_to_update = [
                'check_in_date' => Carbon::parse($this->CHECKOUT_DATE)->addDay()->format('Y-m-d'),
                'check_in_time' => $this->convertToTime('12:00 AM'), #TODO: Recheckin time should be from property settings
                'last_check_in_id' => $this->current_check_in_id,
                'parent_id' => $this->current_check_in_id,
                'present_status' =>'continue',
                'guests' => $this->current_guest_and_room_data['guest_age'],
                'created_by' => $this->user->id
            ];

            $new_check_in_data = $this->updateOrAddAdditionParametersInCheckInData($this->current_check_in_data, $fields_to_update);
            $this->current_check_out_type = 'billing';
            $this->recheck_ins[] = $new_check_in_data;
        }

        // all cases other than regenration of bills
        if ($check_in_type === 'partial') {

            $new_checkin_time = $this->getCheckoutTimeFromBillingRules($this->CHECKOUT_TIME, $this->time_rules); //function to set the new check_in time to avoide charge a service twice

            if ($this->current_guests_array === null) {
                $this->current_check_out_type = 'full';
                return;
            }    
            /**
             * Partial Case checkout to property & room: Test Payload
             * ['2323','23232','23232','23232','23232','23232','23232']
             */
            if (is_array($this->current_guests_array) && is_int($this->current_guests_array[0])) {


                $remaining_guest_ids = array_diff($guest_ids_in_current_checkin, $this->current_guests_array);
                if (empty($remaining_guest_ids)){
                    $this->current_check_out_type = 'full';    
                    return; //in case full checkout
                } 
                    
                $remaining_guests = $guest_collection->whereIn('id', $remaining_guest_ids)->values();


                $fields_to_update = [
                    'check_in_date' => $checkout_date,
                    'check_in_time' => $new_checkin_time,
                    'last_check_in_id' => $this->current_check_in_id,
                    'parent_id' => $this->current_check_in_id,
                    'present_status' =>'continue',
                    'guests' => $remaining_guests,
                    'total_persons' => count($remaining_guest_ids),
                    'created_by' => $this->user->id
                ];
                $new_check_in_data = $this->updateOrAddAdditionParametersInCheckInData($this->current_check_in_data, $fields_to_update);
                $this->current_check_out_type = empty($new_check_in_data) ? 'full' : 're-checkin';
                $this->recheck_ins[] = $new_check_in_data;
            }
            /**
             * Relcation Case : Test Payload
             * [
             *   {
             *       "guest_id": 19625,
             *       "property_id": 8,
             *       "room_number": 2691,
             *       "selected_services": [19,20]
             *   }
             * ]
             */
            if (
                is_array($this->current_guests_array) &&
                !is_int($this->current_guests_array[0]) &&
                empty(array_diff(['guest_id', 'property_id', 'room_number'], array_keys($this->current_guests_array[0])))
            ) {



                $property_wise_relocated_and_recheckin_guests = $this->prepareGuestsDataFromRelocation();


                foreach ($property_wise_relocated_and_recheckin_guests as $property_id => $guest_data_array) {


                    $this_guests = $guest_data_array['guests'];
                    //Get unique Selected Services against each property by sorting throw below function
                    $this_selected_services = $this->sort_and_unique_array($guest_data_array['selected_services']);

                    if (count($this_selected_services) > 1)
                        throw new \Exception("In case of relocation One Property can only have one Set of Services");

                    // in Case Selected Services is empty then additional services available in existing checkin will be used
                    if (count($this_selected_services) > 0) {

                        $property_services_with_rules = PropertyServices::with('propertyServiceRules')->whereIn('id', $this_selected_services[0])->get();

                        $fields_to_update = [
                            'check_in_date' => $checkout_date,
                            'check_in_time' => $new_checkin_time,
                            'last_check_in_id' => $this->current_check_in_id,
                            'parent_id' => $this->current_check_in_id,
                            'present_status' =>'continue',
                            'guests' => $this_guests,
                            'total_persons' => count($this_guests),
                            'property_id' => $property_id,
                            'additional_services' => $property_services_with_rules,
                            'selected_services' => implode(",", $this_selected_services->first()->toArray()),
                            'created_by' => $this->user->id
                        ];
                        $new_check_in_data = $this->updateOrAddAdditionParametersInCheckInData($this->current_check_in_data, $fields_to_update);
                        $this->recheck_ins[] = $new_check_in_data;
                    } else {


                        $fields_to_update = [
                            'check_in_date' => $checkout_date,
                            'check_in_time' => $new_checkin_time,
                            'last_check_in_id' => $this->current_check_in_id,
                            'parent_id' => $this->current_check_in_id,
                            'present_status' =>'continue',
                            'guests' => $this_guests,
                            'total_persons' => count($this_guests),
                            'property_id' => $property_id,
                            'created_by' => $this->user->id
                        ];
                        $new_check_in_data = $this->updateOrAddAdditionParametersInCheckInData($this->current_check_in_data, $fields_to_update);
                        $this->recheck_ins[] = $new_check_in_data;
                    }
                    $this->current_check_out_type == 're-checkin';
                }
            }
        }
    }

    private function createRelocatedCheckins($check_in_data)
    {
    }

    /**
     * Add additional fields for re-checkin(last_check_in_id, parent_id, check_in_date, check_in_time, guest_details) to complete payload
     * @return checkin data with guest details
     */
    private function updateOrAddAdditionParametersInCheckInData($check_in_data, $additional_parameters)
    {
        foreach ($additional_parameters as $param => $value) {
            // parent id only updated in case if new checkin don't have any parent id
            if ($param === 'parent_id' && $value != 0) continue;
            $check_in_data[$param] = $value;
        }
        return $check_in_data;
    }

    /**
     * Get time for new check_in
     * Check if chack_out time is between servide start and end time then time for new check_in will be adding 1 minute of in service end time
     * @return checkin time for new check_in
     */
    private function getCheckoutTimeFromBillingRules($checkout_time, array $time_rules)
    {
        $to = collect($this->current_check_in_data['additional_services'])->flatMap(function ($item) use ($checkout_time, $time_rules) {

            return collect($item['billing_rules'])->filter(function ($fields) use ($checkout_time, $time_rules) {
                return in_array(strtolower($fields['charge_compare_with']), $time_rules) &&
                    Carbon::parse($checkout_time)->between(Carbon::parse($fields['from']), Carbon::parse($fields['to']));
            })->pluck('to');
        });

        return (count($to) > 0) ? Carbon::parse($to[0])->addMinute(1)->format("H:i:s") : $checkout_time;
    }

    private function prepareGuestsDataFromRelocation()
    {

        $guest_data = $this->current_guest_and_room_data;

        $guest_collection = (isset($guest_data['guest_age']) && count($guest_data['guest_age']) > 0) ? collect($guest_data['guest_age']) : collect([]);

        $guest_ids_to_relocate = collect($this->current_guests_array)->pluck('guest_id')->toArray();

        $guests_to_relocate = $guest_collection->whereNotIn('id', $guest_ids_to_relocate)->values();

        //merge both guests to recheck_in and relocate and group by property_id
        $combine_relocated_guests_and_recheckin_guests_by_property = collect($this->current_guests_array)->merge($guests_to_relocate)->groupBy('property_id');

        return $combine_relocated_guests_and_recheckin_guests_by_property->map(function ($items) use ($guest_data) {

            $col = collect($items);
            $guests = collect($guest_data['guest_age']);

            /** 
             * if Guests grouped as Property has "id" as Key which means this guest from remaining guest which are not relocated so in that case selected services recieved through user selection will be ignored.
             */

            $selected_services = $col->pluck('selected_services')->sort()->filter()->values();
            $reCheckin_ids = $col->pluck('id')->filter()->values();

            $guests = $col->map(function ($item) use ($guests) {

                if (isset($item['selected_services']) && empty($guest_ids)) {

                    $guest = $guests->where('id', $item['guest_id'])->first();
                    $guest['room_number'] = $item['room_number'];
                    $guest['property_id'] = $item['property_id'];
                    return $guest;
                }
                if (isset($item['id'])) {
                    $guest = $guests->where('id', $item['id'])->first();
                    $guest['room_number'] = $item['room_number'];
                    $guest['property_id'] = $item['property_id'];
                    return $guest;
                }
            });
            return ['guests' => $guests, 'selected_services' => (count($reCheckin_ids) > 0) ? [] : $selected_services];
        });
    }

    private function sort_and_unique_array($array_of_array)
    {
        return collect($array_of_array)->map(function ($array) {
            return collect($array)->sort()->values();
        })->unique()->values();
    }

    public function getReCheckins()
    {
        return $this->recheck_ins;
    }
}
