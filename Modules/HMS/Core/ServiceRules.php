<?php
namespace Modules\HMS\Core;

use Throwable;

class ServiceRules{
    //function rule where charge compare with is both checkin and checkout time
    public static function checkinCheckoutTime($data, $rule, $guests_with_age, $guest_count, $date_and_time, $service){
        try{
            $service_billing_rules = $service['billing_rules'];
            //get all assigned rules for assigned additional services where charges depends on guest age
            $charge_percentage_rule = collect($service_billing_rules)->where('charge_compare_with', 'Guest Age')->toArray();
            $from = date("H:i:s", strtotime( $rule['from']));
            $to = date("H:i:s", strtotime( $rule['to']));
            $checkInTime = date("H:i:s", strtotime( $date_and_time['check_in_time']));
            $checkOutTime = date("H:i:s", strtotime( $date_and_time['check_out_time']));
            $service_name = $service['service_name'];
            $service_cost = $service['cost'];
            $service_selling_price = $service['selling_price'];
            $selling_skip_meals = 0; $cost_skip_meals = 0; $payable = []; $billing = [];
            if($checkInTime > $to){
                unset( $data['cost']);
                $data['item_name'] = $service_name." Skipped due to ".$rule['charge_compare_with']."Based ON Checkin Time";
                $data['uom'] = 'No Of Guests';
                $data['quantity'] = $guest_count;
                $data['days'] = -1;
                $data['selling_price'] = $service_selling_price;
                foreach($charge_percentage_rule as $p_rule){
                    foreach($guests_with_age as $age){
                        if($age['age'] >= $p_rule['from'] && $age['age'] <= $p_rule['to']){
                            $selling_skip_meals += ($p_rule['charge_percentage'] * $service_selling_price)/100;
                            $cost_skip_meals += ($p_rule['charge_percentage'] * $service_cost)/100;
                        }
                    }
                }
                $data['total_amount'] = $data['days'] * $selling_skip_meals;
                if($rule['apply_on'] === 'Selling' || $rule['apply_on'] === 'Both'){
                    $billing[] = $data;
                }
                unset( $data['selling_price']);
                if($service_cost > 0){ //if cost is zero then no discount field will be added
                    $data['cost'] = $service_cost;
                    $data['total_amount'] = $data['days'] * $cost_skip_meals;
                    if($rule['apply_on'] === 'Cost' || $rule['apply_on'] === 'Both'){
                        $payable[] = $data;
                    }
                }
            }
            //11:00 AM
            if($checkOutTime <= $from){
                unset( $data['cost']);
                $data['item_name'] = $service_name." Skipped due to ".$rule['charge_compare_with']."Based ON Checkout Time";
                $data['uom'] = 'No Of Guests';
                $data['quantity'] = $guest_count;
                $data['days'] = -1;
                $data['selling_price'] = $service_selling_price;
                foreach($charge_percentage_rule as $p_rule){
                    foreach($guests_with_age as $age){
                        if($age['age'] >= $p_rule['from'] && $age['age'] <= $p_rule['to']){
                            $selling_skip_meals += ($p_rule['charge_percentage'] * $service_selling_price)/100;
                            $cost_skip_meals += ($p_rule['charge_percentage'] * $service_cost)/100;
                        }
                    }
                }
                $data['total_amount'] = $data['days'] * $selling_skip_meals;
                if($rule['apply_on'] === 'Selling' || $rule['apply_on'] === 'Both'){
                    $billing[] = $data;
                }
                unset( $data['selling_price']);
                if($service_cost > 0){ //if cost is zero then no discount field will be added
                    $data['cost'] = $service_cost;
                    $data['total_amount'] = $data['days'] * $cost_skip_meals;
                    if($rule['apply_on'] === 'Cost' || $rule['apply_on'] === 'Both'){
                        $payable[] = $data;
                    }
                }
            }
            return ['payable' => $payable, 'billing' => $billing];
        }
        catch(Throwable $th){
            return ['error' => $th->getMessage()];
        }
    }

    //function rule where charge compare with is checkin time only
    public static function checkinTime($data, $rule, $guests_with_age, $guest_count, $date_and_time, $service){
        try{
            $service_billing_rules = $service['billing_rules'];
            //get all assigned rules for assigned additional services where charges depends on guest age
            $charge_percentage_rule = collect($service_billing_rules)->where('charge_compare_with', 'Guest Age')->toArray();
            $to = date("H:i:s", strtotime( $rule['to']));
            $checkInTime = date("H:i:s", strtotime( $date_and_time['check_in_time']));
            $service_name = $service['service_name'];
            $service_cost = $service['cost'];
            $service_selling_price = $service['selling_price'];
            $selling_skip_meals = 0; $cost_skip_meals = 0; $payable = []; $billing = [];
            if($checkInTime > $to){
                unset( $data['cost']);
                $data['item_name'] = $service_name." Skipped due to ".$rule['charge_compare_with']."Based ON Checkin Time";
                $data['uom'] = 'No Of Guests';
                $data['quantity'] = $guest_count;
                $data['days'] = -1;
                $data['selling_price'] = $service_selling_price;
                foreach($charge_percentage_rule as $p_rule){
                    foreach($guests_with_age as $age){
                        if($age['age'] >= $p_rule['from'] && $age['age'] <= $p_rule['to']){
                            $selling_skip_meals += ($p_rule['charge_percentage'] * $service_selling_price)/100;
                            $cost_skip_meals += ($p_rule['charge_percentage'] * $service_cost)/100;
                        }
                    }
                }
                $data['total_amount'] = $data['days'] * $selling_skip_meals;
                if($rule['apply_on'] === 'Selling' || $rule['apply_on'] === 'Both'){
                    $billing[] = $data;
                }
                unset( $data['selling_price']);
                if($service_cost > 0){ //if cost is zero then no discount field will be added
                    $data['cost'] = $service_cost;
                    $data['total_amount'] = $data['days'] * $cost_skip_meals;
                    if($rule['apply_on'] === 'Cost' || $rule['apply_on'] === 'Both'){
                        $payable[] = $data;
                    }
                }
            }
            return ['payable' => $payable, 'billing' => $billing];
        }
        catch(Throwable $th){
            return ['error' => $th->getMessage()];
        }
    }

    //function rule where charge compare with is checkin time only
    public static function checkoutTime($data, $rule, $guests_with_age, $guest_count, $date_and_time, $service){
        try{
            $service_billing_rules = $service['billing_rules'];
            //get all assigned rules for assigned additional services where charges depends on guest age
            $charge_percentage_rule = collect($service_billing_rules)->where('charge_compare_with', 'Guest Age')->toArray();
            $from = date("H:i:s", strtotime( $rule['from']));
            $checkOutTime = date("H:i:s", strtotime( $date_and_time['check_out_time']));
            $service_name = $service['service_name'];
            $service_cost = $service['cost'];
            $service_selling_price = $service['selling_price'];
            $selling_skip_meals = 0; $cost_skip_meals = 0; $payable = []; $billing = [];
            if($checkOutTime <= $from){
                unset( $data['cost']);
                $data['item_name'] = $service_name." Skipped due to ".$rule['charge_compare_with']."Based ON Checkout Time";
                $data['uom'] = 'No Of Guests';
                $data['quantity'] = $guest_count;
                $data['days'] = -1;
                $data['selling_price'] = $service_selling_price;
                foreach($charge_percentage_rule as $p_rule){
                    foreach($guests_with_age as $age){
                        if($age['age'] >= $p_rule['from'] && $age['age'] <= $p_rule['to']){
                            $selling_skip_meals += ($p_rule['charge_percentage'] * $service_selling_price)/100;
                            $cost_skip_meals += ($p_rule['charge_percentage'] * $service_cost)/100;
                        }
                    }
                }
                $data['total_amount'] = $data['days'] * $selling_skip_meals;
                if($rule['apply_on'] === 'Selling' || $rule['apply_on'] === 'Both'){
                    $billing[] = $data;
                }
                unset( $data['selling_price']);
                if($service_cost > 0){ //if cost is zero then no discount field will be added
                    $data['cost'] = $service_cost;
                    $data['total_amount'] = $data['days'] * $cost_skip_meals;
                    if($rule['apply_on'] === 'Cost' || $rule['apply_on'] === 'Both'){
                        $payable[] = $data;
                    }
                }
            }
            return ['payable' => $payable, 'billing' => $billing];
        }
        catch(Throwable $th){
            return ['error' => $th->getMessage()];
        }
    }

    //function rule where charge compare with is guest Age
    public static function guestAge($data, $rule, $guests_with_age, $stayDuration, $service){
        try{
            $chargeMultiplier = (100 - $rule['charge_percentage'])/100;
            $service_name = $service['service_name'];
            $service_cost = $service['cost'];
            $service_selling_price = $service['selling_price'];
            foreach($guests_with_age as $guest){
                $discount_cost = 0;
                $discount_selling_price = 0;
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
                    if($rule['apply_on'] === 'Selling' || $rule['apply_on'] === 'Both'){
                        $billing[] = $data;
                    }
                    unset( $data['selling_price']);
                    if($service_cost > 0){ //if cost is zero then no discount field will be added
                        $data['cost'] = $discount_cost;
                        $data['total_amount'] = $data['cost'] * $stayDuration *  $data['quantity'];
                        if($rule['apply_on'] === 'Cost' || $rule['apply_on'] === 'Both'){
                            $payable[] = $data;
                        }
                    }
                }
            }         
            return ['payable' => $payable, 'billing' => $billing];
        }
        catch(Throwable $th){
            return ['error' => $th->getMessage()];
        }
    }

    //function rule where charge compare with is guest Age
    public static function guestsInRoom($data, $rule, $guests_with_age, $date_and_time, $stayDuration, $service){
        try{
            $service_name = $service['service_name'];
            $service_cost = $service['cost'];
            $service_selling_price = $service['selling_price'];
            $service_billing_rules = $service['billing_rules'];
            $chargeable_age = collect($service_billing_rules)->where('charge_percentage', '==', 100)->where('charge_compare_with', 'Guest Age')->pluck('from')->first();
            $complementry_guests = $rule['to'];
            $room_with_guest = collect($guests_with_age)->groupBy('room_number')->toArray();
            foreach($room_with_guest as $room){
                $age_rule_exist = collect($service_billing_rules)->where('charge_compare_with', 'Guest Age')->pluck('from')->first();
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
                    $service_time = collect($service_billing_rules)->where('charge_compare_with', 'Checkin_Checkout_Time')->first();
                    $service_start_time = date("H:i:s", strtotime($service_time['from']));
                    $service_end_time = date("H:i:s", strtotime($service_time['to']));
                    $checkinTime = date("H:i:s", strtotime( $date_and_time['check_in_time']));
                    $checkoutTime = date("H:i:s", strtotime( $date_and_time['check_out_time']));
                    unset( $data['cost']);
                    $data['item_name'] = $service_name." Discount due to ".$rule['title']." on ".$rule['charge_compare_with'];
                    $data['uom'] = 'No Of Guests';
                    $data['quantity'] = $number_of_complementry_guests;
                    $data['days'] = -$stayDuration;
                    if($checkinTime >= $service_end_time){
                        $data['days']++;
                    }
                    if($checkoutTime <= $service_start_time){
                        $data['days']++;
                    }
                    $data['selling_price'] = $service_selling_price;
                    $selling_complementry_meals = ((100 - $rule['charge_percentage']) * $service_selling_price)/100;
                    $cost_complementry_meals = ((100 - $rule['charge_percentage']) * $service_cost)/100;
                    
                    $data['total_amount'] = $data['days'] * $selling_complementry_meals * $data['quantity'];
                    if($rule['apply_on'] === 'Selling' || $rule['apply_on'] === 'Both'){
                        $billing[] = $data;
                    }
                    unset( $data['selling_price']);
                    if($service_cost > 0){ //if cost is zero then no discount field will be added
                        $data['cost'] = $service_cost;
                        $data['total_amount'] = $data['days'] * $cost_complementry_meals * $data['quantity'];
                        if($rule['apply_on'] === 'Cost' || $rule['apply_on'] === 'Both'){
                            $payable[] = $data;
                        }
                    }
                }
            }     
            return ['payable' => $payable, 'billing' => $billing];
        }
        catch(Throwable $th){
            return ['error' => $th->getMessage()];
        }
    }
}