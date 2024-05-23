<?php

namespace Modules\HMS\App\Http\Controllers\API;

use App\Core\ServiceRules;
use Modules\HMS\App\Models\CheckOut;
use Modules\HMS\App\Http\Requests\StoreCheckOutRequest;
use Modules\HMS\App\Http\Requests\UpdateCheckOutRequest;
use Modules\HMS\App\Http\Resources\CheckOutResource;
use Modules\HMS\App\Utilities\Helper;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Modules\HMS\App\Http\Controllers\Controller;
use Modules\HMS\App\Models\CheckedInMembers;
use Modules\HMS\App\Models\CheckIn;
use Modules\HMS\Traits\PaymentDataCreater;
use Modules\HMS\Traits\ProcessCheckin;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class CheckOutController extends Controller
{
    use ProcessCheckin;
    use PaymentDataCreater;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try
        {
            // $checkOut = CheckOut::where('is_deleted', 0)->get(); // Only fetch companies with is_deleted = 0
            return Helper::successResponse(CheckOutResource::collection(CheckOut::all()));
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
    public function store(StoreCheckOutRequest $request)
    {
        DB::beginTransaction();
        try{
            $this->process_checkout($request,[],['type' => 'partial']);
            $re_checkins = $this->getReCheckins();
            if(empty($re_checkins)){
                DB::commit();
                return Helper::successResponse(null, 'Successfully checked out');
            }
            else{
                $response = $this->process_checkins( $re_checkins, 're_checkins');
                DB::commit();
                return  Helper::successResponse($response, 'Successfully checked out');
            }
        }    
        catch (\Throwable $th) {
            DB::rollback();
            return Helper::errorResponse($th->getMessage());
        }
    }
    
    
    
    //function to calculate total cost and selling price for rooms and services
    public static function property_billing($check_in_data, $billing_data,$guests,$user,$check_out_date,$check_out_time, $check_out_type){
        try{
            if(is_array($billing_data)){
                $billing = [];
                $payable = [];
                $i = 0;
                foreach($billing_data as $fields){
                    $property_id = $fields['property_id'];
                    $check_in_id = $fields['id'];
                    $data['check_in_id'] = $check_in_id;
                    $data['property_id'] = $property_id;
                    $data['created_by'] = $user->id;
                    $checkOutDate = Carbon::parse($check_out_date);
                    $checkOutTime = date("H:i:s", strtotime($check_out_time));
                    $check_in_date = Carbon::parse($fields['check_in_date']);
                    $check_in_time = date("H:i:s", strtotime($fields['check_in_time']));
                    
                    //create array of checkin and checkout date time
                    $date_and_time = ['check_in_date' => $check_in_date, 'check_in_time' => $check_in_time, 'check_out_date' => $checkOutDate, 'check_out_time' => $checkOutTime ];
                    
                    //calculate stay duration according to checkin and checkout date
                    $stayDuration = $checkOutDate->diffInDays($check_in_date) + 1; // to include start date
                    
                    //count total number of guest in a checkin
                    $guest_count = $guests[$check_in_id]['guest_count'];
                    $rooms = $guests[$check_in_id]['room_rent']; // is an array
                    
                    //guest with the age as services are charged according to guest age
                    $guests_with_age = $guests[$check_in_id]['guest_age'];
                    
                    //get list of rooms and count number of rooms assignes to a checkin 
                    $array_of_rooms = collect($rooms)->pluck('count')->unique()->first();
                    $total_rooms = collect($array_of_rooms)->sum();
                    
                    //helper function to get stay_duration for rooms in case of partial, relocate and full checkout 
                    $days_nights = Helper::calculateDaysNights($fields['check_in_date'], $check_in_time, $check_out_date, $checkOutTime, $property_id);
                    if(isset($days_nights['error'])){
                        return ['error' => $days_nights['error']];
                    }
                    if($check_out_type === 'full'){
                        $room_stay_duration = $days_nights['stay_duration'];//in case of full checkout
                    }
                    else if($check_out_type === 'billing'){
                        $room_stay_duration = $days_nights['partial_duration'] + 1;//in case generate missing bills to include current day
                    }
                    else{
                        $room_stay_duration = $days_nights['partial_duration'];//in case of relocate, partial checkout
                    }
    
                    //calculate Room rentals
                    foreach($rooms as $room){
                        unset($data['cost']);
                        $data['quantity'] = $room['count'];
                        $data['days'] = $room_stay_duration;
                        $data['assigned_additional_service_id'] = 0;
                        $data['item_name'] = "Room Rent For ".$room['Room_type'];
                        $data['total_amount'] = $data['quantity'] * $data['days'] * $room['Selling_price'];
                        $data['selling_price'] =  $room['Selling_price'];
                        $data['uom'] = 'days';
                        $billing[$i] = $data;
                        unset($data['selling_price']);
                        $data['total_amount'] = $data['quantity'] * $data['days'] * $room['Hiring_cost'];
                        $data['cost'] =  $room['Hiring_cost'];
                        $payable[$i] = $data;
                        $i++;
                    }
                    //calculate Additional Services
                    if($fields['additional_services'] && is_array($fields['additional_services'])){
                        foreach($fields['additional_services'] as $service){
                            $data['check_in_id'] = $check_in_id;
                            $data['assigned_additional_service_id'] = $service['id'];
                            $data['property_id'] = $property_id;
                            $data['created_by'] = $user->id;
                            $data['item_name'] = $service['service_name'];
                            
                            if(strtolower($service['basis_of_application']) == 'guests'){
                                
                                $data['uom'] = 'No Of Guests';
                                // in case of No Billing Rules
                                unset($data['cost']);
                                $data['quantity'] = $guest_count;
                                if(strtolower($fields['check_in_type'] )== 'event'){
                                    $data['quantity'] = $fields['total_persons'];
                                }
                                $data['days'] = $stayDuration; 
                                $data['selling_price'] = $service['selling_price'];
                                $data['total_amount'] =  $data['quantity'] * $data['days'] * $service['selling_price'];
                                if($data['total_amount'] < 0){
                                    $data['total_amount'] = 0;
                                }
                                $billing[$i] = $data;
                                unset($data['selling_price']);
                                $data['cost'] = $service['cost'];
                                $data['total_amount'] =  $data['quantity'] * $data['days'] * $service['cost'];
                                if($data['total_amount'] < 0){
                                    $data['total_amount'] = 0;
                                }
                                $payable[$i] = $data;
                                if($service['billing_rules'] && is_array($service['billing_rules']) && $data['total_amount'] !== 0){
                                    $rules_data = CheckOutController::billingRules($service, $date_and_time, $guest_count, $guests_with_age,$property_id,$user->id,$check_in_id,$stayDuration);
                                    if($rules_data['billing'] && (!empty($rules_data['billing']))){
                                        foreach($rules_data['billing'] as $r_billing){
                                            $i++;
                                            $billing[$i] = $r_billing;
                                        }
                                    }
                                    if($rules_data['payable'] && (!empty($rules_data['payable']))){
                                        foreach($rules_data['payable'] as $r_payable){
                                            $i++;
                                            $payable[$i] = $r_payable;
                                        }
                                    }
                                }
                            }
                            if(strtolower($service['basis_of_application']) == 'family'){
                                unset($data['cost']);
                                $data['uom'] = 'Whole Group';
                                $data['quantity'] = 1;
                                $data['days'] = $stayDuration;
                                $data['selling_price'] = $service['selling_price'];
                                $data['total_amount'] =  $data['days'] * $service['selling_price'];
                                if($service['frequency'] == 'One Time'){
                                    $data['days'] = 0;
                                    $data['total_amount'] = $service['selling_price'];
                                }
                                if($data['total_amount'] < 0){
                                    $data['total_amount'] =0;
                                }
                                $billing[$i] = $data;
                                unset($data['selling_price']);
                                $data['cost'] = $service['cost'];
                                $data['total_amount'] =  $data['days'] * $service['cost'];
                                if($service['frequency'] == 'One Time'){
                                    $data['days'] = 0;
                                    $data['total_amount'] = $service['cost'];
                                }
                                if($data['total_amount'] < 0){
                                    $data['total_amount'] =0;
                                }
                                $payable[$i] = $data;
                            }
                            if(strtolower($service['basis_of_application']) == 'room'){
                                unset($data['cost']);
                                $data['uom'] = 'Number Of Rooms';
                                $data['quantity'] = $total_rooms;
                                $data['days'] = $stayDuration;
                                $data['selling_price'] = $service['selling_price'];
                                $data['total_amount'] =  $data['quantity'] * $data['days'] * $service['selling_price'];
                                if($service['frequency'] == 'One Time'){
                                    $data['days'] = 0;
                                    $data['total_amount'] = $service['selling_price'] * $data['quantity'];
                                }
                                if($data['total_amount'] < 0){
                                    $data['total_amount'] =0;
                                }
                                $billing[$i] = $data;
                                unset($data['selling_price']);
                                $data['cost'] = $service['cost'];
                                $data['total_amount'] =  $data['quantity'] * $data['days'] * $service['cost'];
                                if($service['frequency'] == 'One Time'){
                                    $data['days'] = 0;
                                    $data['total_amount'] = $service['cost'] * $data['quantity'];
                                }
                                if($data['total_amount'] < 0){
                                    $data['total_amount'] =0;
                                }
                                $payable[$i] = $data;
                            }
                            $i++;
                        }
                    }
                }
                return ["payable"=>$payable,"billing"=>$billing];
            }
        }
        catch(Throwable $th){
            return ['error' => $th->getMessage()];
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(CheckOut $checkOut)
    {
        try
        {
            // if ($checkOut->is_deleted == 1) {
            //     return Helper::errorResponse('Record not found', 404);
            // }
            return Helper::successResponse(CheckOutResource::make($checkOut));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CheckOut $checkOut)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCheckOutRequest $request, CheckOut $checkOut)
    {
        try
        {
            $check_out_id = $request->check_out;
            $checkOut = $request->validate();
            $userID = JWTAuth::parseToken()->authenticate();
            $checkOut['updated_by'] = $userID->id;
            CheckOut::where('id', $check_out_id->id)->update($checkOut);
            return Helper::successResponse(CheckOutResource::make($checkOut));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CheckOut $checkOut)
    {
        try{
            // $checkOut->delete();
            if (!$checkOut) {
                return Helper::errorResponse('Record not found', 404);
            }
    
            // Set the is_deleted field to 1
            $checkOut->is_deleted = 1;
            $userID = JWTAuth::parseToken()->authenticate();
            $checkOut->deleted_by = $userID->id;
            $checkOut->save();
    
            return Helper::successResponse('Successfully Deleted', 200);
            // return Helper::successResponse(response()->noContent());
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }


    //function to calculate according to the billing rules of services
    public static function billingRules($service, $date_and_time, $guest_count, $guests_with_age,$property_id,$user_id,$check_in_id,$stayDuration){
        try{
            // $discounts = [];
            // $skipped_services = [];
            $payable = [];
            $billing = [];
            $service_name = $service['service_name'];
            $service_cost = $service['cost'];
            $service_selling_price = $service['selling_price'];
            $service_billing_rules = array_map(function ($record) { 
                return array_map('strtolower', $record);
            }, $service['billing_rules']);
            //get all assigned rules for assigned additional services where charges depends on guest age
            $charge_percentage_rule = collect($service_billing_rules)->where('charge_compare_with', 'guest age')->toArray();
            $data['check_in_id'] = $check_in_id;
            $data['assigned_additional_service_id'] = $service['id'];
            $data['property_id'] = $property_id;
            $data['created_by'] = $user_id;
            $i = 0;
            foreach($service_billing_rules as $rule){
                $rule = array_map('strtolower', $rule);
                // on basis of both checkin and checkout time
                if($rule['charge_compare_with'] == 'checkin_checkout_time'){
                    // $response = ServiceRules::checkinCheckoutTime($data, $rule, $guests_with_age, $guest_count, $date_and_time, $service, $charge_percentage_rule);
                    // if(isset($response['error'])){
                    //     return $response['error'];
                    // } else{
                    //     $payable[] = $response['payable'];
                    //     $billing[] = $response['billing'];
                    // }
                    $from = date("H:i:s", strtotime( $rule['from']));
                    $to = date("H:i:s", strtotime( $rule['to']));
                    $checkInTime = date("H:i:s", strtotime( $date_and_time['check_in_time']));
                    $checkOutTime = date("H:i:s", strtotime( $date_and_time['check_out_time']));
                    $quantity = 0;
                    $chargeMultiplier = $rule['charge_percentage']/100;
                    if($checkInTime > $to){
                        $selling_skip_meals = 0;
                        $cost_skip_meals = 0;
                        unset( $data['cost']);
                        $data['item_name'] = $service_name." Skipped due to ".$rule['charge_compare_with']." Based ON Checkin Time";
                        $data['uom'] = 'No Of Guests';
                        $data['quantity'] = $guest_count;
                        $data['days'] = -1;
                        $data['selling_price'] = $service_selling_price;
                        $array = [];
                        foreach($guests_with_age as $age){
                            foreach($charge_percentage_rule as $p_rule){
                                $is_age_matched = ($age['age'] > $p_rule['from'] && $age['age'] <= $p_rule['to']);
                                $is_in_range = ($is_age_matched)?$is_age_matched:($age['age']+1 > $p_rule['from'] && $age['age'] <= $p_rule['to']);
                                if( $is_in_range ){
                                    if($p_rule['apply_on'] == 'selling'){
                                        $selling_skip_meals += ($p_rule['charge_percentage'] * $service_selling_price)/100;
                                        $cost_skip_meals +=  $service_cost;
                                    }
                                    else if($p_rule['apply_on'] == 'cost'){
                                        $selling_skip_meals +=  $service_selling_price;
                                        $cost_skip_meals += ($p_rule['charge_percentage'] * $service_cost)/100;
                                    }
                                    else{
                                        $selling_skip_meals += ($p_rule['charge_percentage'] * $service_selling_price)/100;
                                        $cost_skip_meals += ($p_rule['charge_percentage'] * $service_cost)/100;
                                    }
                                    break;
                                }
                            }
                        }
                        $data['total_amount'] = $data['days'] * $selling_skip_meals;
                        if($rule['apply_on'] == 'selling' || $rule['apply_on'] == 'both'){
                            $billing[$i] = $data;
                        }
                        // dd(100, $array);
                        unset( $data['selling_price']);
                        if($service_cost > 0){ //if cost is zero then no discount field will be added
                            $data['cost'] = $service_cost;
                            $data['total_amount'] = $data['days'] * $cost_skip_meals;
                            if($rule['apply_on'] == 'cost' || $rule['apply_on'] == 'both'){
                                $payable[$i] = $data;
                            }
                        }
                        $i++;
                    }
                    //11:00 AM
                    if($checkOutTime <= $from){
                        $selling_skip_meals = 0;
                        $cost_skip_meals = 0;
                        unset( $data['cost']);
                        $data['item_name'] = $service_name." Skipped due to ".$rule['charge_compare_with']." Based ON Checkout Time";
                        $data['uom'] = 'No Of Guests';
                        $data['quantity'] = $guest_count;
                        $data['days'] = -1;
                        $data['selling_price'] = $service_selling_price;
                        foreach($guests_with_age as $age){
                            foreach($charge_percentage_rule as $p_rule){
                                $is_age_matched = ($age['age'] > $p_rule['from'] && $age['age'] <= $p_rule['to']);
                                $is_in_range = ($is_age_matched)?$is_age_matched:($age['age']+1 > $p_rule['from'] && $age['age'] <= $p_rule['to']);
                                if( $is_in_range ){
                                    if($p_rule['apply_on'] == 'selling'){
                                        $selling_skip_meals += ($p_rule['charge_percentage'] * $service_selling_price)/100;
                                        $cost_skip_meals +=  $service_cost;
                                    }
                                    else if($p_rule['apply_on'] == 'cost'){
                                        $selling_skip_meals +=  $service_selling_price;
                                        $cost_skip_meals += ($p_rule['charge_percentage'] * $service_cost)/100;
                                    }
                                    else{
                                        $selling_skip_meals += ($p_rule['charge_percentage'] * $service_selling_price)/100;
                                        $cost_skip_meals += ($p_rule['charge_percentage'] * $service_cost)/100;
                                    }
                                    break;
                                }
                            }
                        }
                        $data['total_amount'] = $data['days'] * $selling_skip_meals;
                        if($rule['apply_on'] == 'selling' || $rule['apply_on'] == 'both'){
                            $billing[$i] = $data;
                        }
                        unset( $data['selling_price']);
                        if($service_cost > 0){ //if cost is zero then no discount field will be added
                            $data['cost'] = $service_cost;
                            $data['total_amount'] = $data['days'] * $cost_skip_meals;
                            if($rule['apply_on'] === 'cost' || $rule['apply_on'] === 'both'){
                                $payable[$i] = $data;
                            }
                        }
                        $i++;
                    }
                }
                

                //on basis of checkin time only
                if($rule['charge_compare_with'] === 'checkin time'){
                    // $response = ServiceRules::checkinTime($data, $rule, $guests_with_age, $guest_count, $date_and_time, $service, $charge_percentage_rule);
                    // if(isset($response['error'])){
                    //     return $response['error'];
                    // } else{
                    //     $payable[] = $response['payable'];
                    //     $billing[] = $response['billing'];
                    // }
                    $from = date("H:i:s", strtotime( $rule['from']));
                    $to = date("H:i:s", strtotime( $rule['to']));
                    $checkInTime = date("H:i:s", strtotime( $date_and_time['check_in_time']));
                    $checkOutTime = date("H:i:s", strtotime( $date_and_time['check_out_time']));
                    $selling_skip_meals = 0;
                    $cost_skip_meals = 0;
                    $quantity = 0;
                    $chargeMultiplier = $rule['charge_percentage']/100;
                    if($checkInTime > $to){
                        unset( $data['cost']);
                        $data['item_name'] = $service_name." Skipped due to ".$rule['charge_compare_with']." Based ON Checkin Time";
                        $data['uom'] = 'No Of Guests';
                        $data['quantity'] = $guest_count;
                        $data['days'] = -1;
                        $data['selling_price'] = $service_selling_price;
                        foreach($guests_with_age as $age){
                            foreach($charge_percentage_rule as $p_rule){
                                $is_age_matched = ($age['age'] > $p_rule['from'] && $age['age'] <= $p_rule['to']);
                                $is_in_range = ($is_age_matched)?$is_age_matched:($age['age']+1 > $p_rule['from'] && $age['age'] <= $p_rule['to']);
                                if( $is_in_range ){
                                    if($p_rule['apply_on'] == 'selling'){
                                        $selling_skip_meals += ($p_rule['charge_percentage'] * $service_selling_price)/100;
                                        $cost_skip_meals +=  $service_cost;
                                    }
                                    else if($p_rule['apply_on'] == 'cost'){
                                        $selling_skip_meals +=  $service_selling_price;
                                        $cost_skip_meals += ($p_rule['charge_percentage'] * $service_cost)/100;
                                    }
                                    else{
                                        $selling_skip_meals += ($p_rule['charge_percentage'] * $service_selling_price)/100;
                                        $cost_skip_meals += ($p_rule['charge_percentage'] * $service_cost)/100;
                                    }
                                    break;
                                }
                            }
                        }
                        $data['total_amount'] = $data['days'] * $selling_skip_meals;
                        if($rule['apply_on'] === 'selling' || $rule['apply_on'] === 'both'){
                            $billing[$i] = $data;
                        }
                        unset( $data['selling_price']);
                        if($service_cost > 0){ //if cost is zero then no discount field will be added
                            $data['cost'] = $service_cost;
                            $data['total_amount'] = $data['days'] * $cost_skip_meals;
                            if($rule['apply_on'] === 'cost' || $rule['apply_on'] === 'both'){
                                $payable[$i] = $data;
                            }
                        }
                        $i++;
                    }
                }


                //on basis of checkout time only
                if($rule['charge_compare_with'] == 'checkout time'){
                    // $response = ServiceRules::checkoutTime($data, $rule, $guests_with_age, $guest_count, $date_and_time, $service, $charge_percentage_rule);
                    // if(isset($response['error'])){
                    //     return $response['error'];
                    // } else{
                    //     $payable[] = $response['payable'];
                    //     $billing[] = $response['billing'];
                    // }
                    $from = date("H:i:s", strtotime( $rule['from']));
                    $to = date("H:i:s", strtotime( $rule['to']));
                    $checkInTime = date("H:i:s", strtotime( $date_and_time['check_in_time']));
                    $checkOutTime = date("H:i:s", strtotime( $date_and_time['check_out_time']));
                    $selling_skip_meals = 0;
                    $cost_skip_meals = 0;
                    $quantity = 0;
                    $chargeMultiplier = $rule['charge_percentage']/100;
                    if($checkOutTime <= $from){
                        unset( $data['cost']);
                        $data['item_name'] = $service_name." Skipped due to ".$rule['charge_compare_with']." Based ON Checkout Time";
                        $data['uom'] = 'No Of Guests';
                        $data['quantity'] = $guest_count;
                        $data['days'] = -1;
                        $data['selling_price'] = $service_selling_price;
                        foreach($guests_with_age as $age){
                            foreach($charge_percentage_rule as $p_rule){
                                $is_age_matched = ($age['age'] > $p_rule['from'] && $age['age'] <= $p_rule['to']);
                                $is_in_range = ($is_age_matched)?$is_age_matched:($age['age']+1 > $p_rule['from'] && $age['age'] <= $p_rule['to']);
                                if( $is_in_range ){
                                    if($p_rule['apply_on'] == 'selling'){
                                        $selling_skip_meals += ($p_rule['charge_percentage'] * $service_selling_price)/100;
                                        $cost_skip_meals +=  $service_cost;
                                    }
                                    else if($p_rule['apply_on'] == 'cost'){
                                        $selling_skip_meals +=  $service_selling_price;
                                        $cost_skip_meals += ($p_rule['charge_percentage'] * $service_cost)/100;
                                    }
                                    else{
                                        $selling_skip_meals += ($p_rule['charge_percentage'] * $service_selling_price)/100;
                                        $cost_skip_meals += ($p_rule['charge_percentage'] * $service_cost)/100;
                                    }
                                    break;
                                }
                            }
                        }
                        $data['total_amount'] = $data['days'] * $selling_skip_meals;
                        if($rule['apply_on'] === 'selling' || $rule['apply_on'] === 'both'){
                            $billing[$i] = $data;
                        }
                        unset( $data['selling_price']);
                        if($service_cost > 0){ //if cost is zero then no discount field will be added
                            $data['cost'] = $service_cost;
                            $data['total_amount'] = $data['days'] * $cost_skip_meals;
                            if($rule['apply_on'] === 'cost' || $rule['apply_on'] === 'both'){
                                $payable[$i] = $data;
                            }
                        }
                        $i++;
                    }
                }


                //on basis of guest age
                if($rule['charge_compare_with'] === 'guest age'){
                    // $response = ServiceRules::guestAge($data, $rule, $guests_with_age, $stayDuration, $service);
                    // if(isset($response['error'])){
                    //     return $response['error'];
                    // } else{
                    //     $payable[] = $response['payable'];
                    //     $billing[] = $response['billing'];
                    // }
                    $chargeMultiplier = (100 - $rule['charge_percentage'])/100;
                    
                    
                    foreach($guests_with_age as $guest){
                        
                        $discount_cost = 0;
                        $discount_selling_price = 0;
                        $discount_qty = 0;
                        if($guest['age'] >= $rule['from'] && $guest['age'] <= $rule['to']){
                        
                            $discount_cost +=  $chargeMultiplier * $service_cost ;
                            $discount_selling_price += $chargeMultiplier * $service_selling_price;
                            
                            if($discount_selling_price <= 0){
                                continue;
                            }
                            unset( $data['cost']);
                            $data['item_name'] = $service_name." Discounts on ".$rule['title'];
                            $data['uom'] = 'No Of Guests';
                            $data['quantity'] = -1;
                            $data['days'] = $stayDuration;
                            $data['selling_price'] = $discount_selling_price;
                            $data['total_amount'] =  $data['selling_price'] * $stayDuration *  $data['quantity'];
                            if($rule['apply_on'] === 'selling' || $rule['apply_on'] === 'both'){
                                $billing[$i] = $data;
                            }
                            unset( $data['selling_price']);
                            if($service_cost > 0){ //if cost is zero then no discount field will be added
                                $data['cost'] = $discount_cost;
                                $data['total_amount'] = $data['cost'] * $stayDuration *  $data['quantity'];
                                if($rule['apply_on'] == 'cost' || $rule['apply_on'] == 'both'){
                                    $payable[$i] = $data;
                                }
                            }
                            $i++;
                        }
                    }                  
                }

                //on basis of number of guests in a room
                if($rule['charge_compare_with'] == 'number of guest in rooms'){
                    // $response = ServiceRules::guestsInRoom($data, $rule, $guests_with_age, $date_and_time, $stayDuration, $service);
                    // if(isset($response['error'])){
                    //     return $response['error'];
                    // } else{
                    //     $payable[] = $response['payable'];
                    //     $billing[] = $response['billing'];
                    // }
                    $chargeable_age = collect($service_billing_rules)->where('charge_percentage', '==', 100)->where('charge_compare_with', 'guest age')->pluck('from')->first();
                    $complementry_guests = $rule['to'];
                    $room_with_guest = collect($guests_with_age)->groupBy('room_number')->toArray();
                    foreach($room_with_guest as $room){
                        $age_rule_exist = collect($service_billing_rules)->where('charge_compare_with', 'guest age')->pluck('from')->first();
                        if($age_rule_exist === null){
                            $number_of_complementry_guests = $complementry_guests;
                        }
                        else{
                            $complementry_discount = collect($room)->where('age', '>=', $chargeable_age)->toArray();
                            $number_of_complementry_guests = count($complementry_discount);
                            if($number_of_complementry_guests > $complementry_guests){
                                $number_of_complementry_guests =  $complementry_guests;
                            }
                        }
                        if($number_of_complementry_guests > 0){
                            $service_time = collect($service_billing_rules)->where('charge_compare_with', 'checkin_checkout_time')->first();
                            $service_start_time = date("H:i:s", strtotime($service_time['from']));
                            $service_end_time = date("H:i:s", strtotime($service_time['to']));
                            $checkinTime = date("H:i:s", strtotime( $date_and_time['check_in_time']));
                            $checkoutTime = date("H:i:s", strtotime( $date_and_time['check_out_time']));
                            unset( $data['cost']);
                            $data['item_name'] = $service_name." Discount due to ".$rule['title']." on ".$rule['charge_compare_with'];
                            $data['uom'] = 'No Of Guests';
                            $data['quantity'] = $number_of_complementry_guests;
                            //days in complementry discount will be 0 and which meas it is equal to days of service to avoid errors on merge discounts because it cause -ve days as discounts days are subtracted from service's day
                            $days = -$stayDuration;
                            $data['days'] = 0;
                            //if service is skipped because of checkin or checkout time then number of days will be less by 1 for each checkin and checkout because 
                            if($checkinTime >= $service_end_time){//TODO what if discount on checkin and checkout is only for cost or selling
                                $days++;
                            }
                            if($checkoutTime <= $service_start_time){
                                $days++;
                            }
                            $data['selling_price'] = $service_selling_price;
                            $selling_complementry_meals = ((100 - $rule['charge_percentage']) * $service_selling_price)/100;
                            $cost_complementry_meals = ((100 - $rule['charge_percentage']) * $service_cost)/100;
                            
                            $data['total_amount'] = $days * $selling_complementry_meals * $data['quantity'];
                            if($rule['apply_on'] == 'selling' || $rule['apply_on'] == 'both'){
                                $billing[$i] = $data;
                            }
                            unset( $data['selling_price']);
                            if($service_cost > 0){ //if cost is zero then no discount field will be added
                                $data['cost'] = $service_cost;
                                $data['total_amount'] = $days * $cost_complementry_meals * $data['quantity'];
                                if($rule['apply_on'] == 'cost' || $rule['apply_on'] == 'both'){
                                    $payable[$i] = $data;
                                }
                            }
                            $i++;
                            // dd($total_complementry_guests, $chargeable_age);
                        }
                    }
                }
                $i++;
            }
            return ['payable' => $payable, 'billing' => $billing];
        }
        catch(Throwable $th){
            return $th->getMessage();
        }
    }


    //function to checkout the active checkins
    public function checkOut($request){
        DB::beginTransaction();
        try{
            $userID = JWTAuth::parseToken()->authenticate(); 
            date_default_timezone_set("Asia/Karachi");
            $CHECK_OUT_TIME = (!isset($request['check_out_time']) || empty($request['check_out_time'])) ? Carbon::now()->toTimeString() : date("H:i:s", strtotime($request['check_out_time']));
            $CHECK_OUT_DATE = (!isset($request['check_out_date']) || empty($request['check_out_date'])) ? Carbon::now()->toDateString() : $request['check_out_date'];
            $present_status = ($request['check_out_type'] == 'full') ? 'checked_out' : 'continue';
            // if(!$request->has('check_in_data') || ($request->has('check_in_data') && !is_array($request->check_in_data))){
                if(!isset($request['check_in_data']) || (isset($request['check_in_data']) && !is_array($request['check_in_data']))){
                    return ['error' => 'Request is incomplete'];
                }
                $check_in_data = $request['check_in_data'];
                $checkInIds = [];
                foreach ($check_in_data as $checkIn) {
                    $checkInIds[] = $checkIn['check_in_id'];
                }
            $checkInIds = array_unique($checkInIds);
            $checkOuts = CheckIn::find( $checkInIds )->toArray();
            $count = collect( $checkOuts )->where('check_in_status','checked_out')->count();
            if($count>0) return ['error' => "Requested $count Check-ins already Checked out"];
            $invalid_dateTime = [];
            foreach($checkOuts as $item){
                $in_timeDate = $item['check_in_date'].' '.$item['check_in_time'];
                $out_timeDate = $CHECK_OUT_DATE.' '.$CHECK_OUT_TIME;
                if(strtotime($in_timeDate) > strtotime($out_timeDate)){
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
                return ['error' => 'Checkout date-time must be after or equal to checkin date-time for '.$errors];
            }
            // update Status and CHECOUT_DATE & TIME in CHECKIn Table;
            CheckIn::whereIn('id',$checkInIds)->update(['check_in_status'=>'checked_out', 'present_status' => $present_status, 'check_out_date'=> $CHECK_OUT_DATE,'check_out_time'=>$CHECK_OUT_TIME]);
            $checkInData = CheckedInMembers::with(['rooms','rooms.roomtype'])->whereIn('check_in_id', $checkInIds)->get();
            
            $guest_and_room_count = $checkInData->groupBy('check_in_id')->map(function ($group) {
                
                $count =  $group->pluck('rooms.roomtype.room_type')->all();
                $uniqueRoomTypes = $group->pluck('rooms.roomtype.room_type')->unique()->values()->all();
                $uniqueRoomNumbers = collect($group)->unique('room_number')->toArray();
                $roomTypeWiseData = collect($uniqueRoomTypes)->map(function ($roomType) use ($group, $uniqueRoomNumbers) {
                    $count = 0;
                    foreach($uniqueRoomNumbers as  $value){
                        if($value['rooms']['roomtype']['room_type'] === $roomType){
                            $c = collect($uniqueRoomNumbers)->filter(function ($record) use ($roomType) {
                                return $record['rooms']['roomtype']['room_type'] === $roomType;
                            })->count();
                            // dd($c);
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
                $guestAges = $group->map(function ($guest) {
                    return [
                        "id" => $guest['id'],
                        "name" => $guest['guest_name'],
                        "age" => now()->diffInYears($guest['date_of_birth']),
                        "room_number" => $guest['room_number'],
                    ];
                })->values()->all();
                
                return [
                    "guest_count" => $group->count('guest_name'),
                    "room_rent" => $roomTypeWiseData,
                    'guest_age'=>$guestAges
                ];
            });
            
            // return $guest_and_room_count;
            $room_ids = collect($checkInData)->pluck('room_number')->unique()->toArray();
            
            //get allassigned services to checkin with their billing rules
            // dd(10);
            $billing_data = CheckIn::with(['additionalServices','additionalServices.billingRules'])->whereIn('id',$checkInIds )->get()->toArray();
            
            
            $finalCharges = $this->property_billing($check_in_data, $billing_data,$guest_and_room_count,$userID,$CHECK_OUT_DATE , $CHECK_OUT_TIME, $request['check_out_type']);
            if(isset($finalCharges['error'])){
                return ['error' => $finalCharges['error']];
            }
            DB::table('property_billings')->insert($finalCharges['billing']);
            DB::table('payables')->insert($finalCharges['payable']);
            DB::table('room_lists')->whereIn('id', $room_ids)->where('room_status', 'occupied')->update(['room_status' => 'available', 'updated_by' => $userID->id, 'check_in_date' => null, 'check_out_date' => null]);
            $check_in_controller = app(CheckInController::class);
            $response = null;
            // dd($check_in_data);
            if($request['check_out_type'] === 'partial'){
                $response = $check_in_controller->checkin_from_existing_data($check_in_data, $CHECK_OUT_DATE, $CHECK_OUT_TIME, $userID);
            }
            else if($request['check_out_type'] === 'relocate'){
                $response = $check_in_controller->relocate($check_in_data, $CHECK_OUT_DATE, $CHECK_OUT_TIME, $userID);
            }
            else{
                $response = "OK";//in case of full checkout response is OK
            }
            // return $response;
            // dd(10);
            // dd($response);
            if($response == "OK"){
                DB::commit();
                $check_out_id = collect($check_in_data)->pluck('check_in_id')->toArray();
                $check_out_data = CheckIn::whereIn('id', $check_out_id)->get();
                return CheckOutResource::collection($check_out_data);
            }
            else{
                DB::rollback();
                return ['error' => $response];
            }
        }
        catch (\Throwable $th) {
            DB::rollback();
            return ['error' => $th->getMessage()];
        }
    }

    public function chargePercantage($p_rule, $age){

    }
}
