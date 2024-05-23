<?php
namespace Modules\HMS\App\Core;

use Modules\HMS\App\Http\Controllers\API\CheckInController;
use Modules\HMS\App\Http\Controllers\API\CheckOutController;
use Modules\HMS\App\Http\Resources\CheckOutResource;
use Modules\HMS\App\Models\CheckedInMembers;
use Modules\HMS\App\Models\CheckIn;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class CheckOutData{
    //function to checkout data
    public static function CheckOut($request){
        DB::beginTransaction();
        try{
            $userID = JWTAuth::parseToken()->authenticate(); 
            date_default_timezone_set("Asia/Karachi");
            $CHECK_OUT_TIME = (!isset($request['check_out_time']) || empty($request['check_out_time'])) ? Carbon::now()->toTimeString() : date("H:i:s", strtotime($request['check_out_time']));
            $CHECK_OUT_DATE = (!isset($request['check_out_date']) || empty($request['check_out_date'])) ? Carbon::now()->toDateString() : $request['check_out_date'];
            if(!isset($request['check_in_data']) || (isset($request['check_in_data']) && !is_array($request['check_in_data']))){
                return ['error' => 'Request is incomplete'];
            }
            $check_in_data = $request['check_in_data'];
            
            $checkInIds = [];
            foreach ($check_in_data as $checkIn) {
                $checkInIds[] = $checkIn['check_in_id'];
            }
            $checkInIds = array_unique($checkInIds);
            
            
            $count = CheckIn::find( $checkInIds )->where('check_in_status','checked_out')->count();
            
            if($count) return ['error' => "Requested $count Check-ins already Checked out"];
            
            // update Status and CHECOUT_DATE & TIME in CHECKIn Table;
            CheckIn::whereIn('id',$checkInIds)->update(['check_in_status'=>'checked_out','check_out_date'=> $CHECK_OUT_DATE,'check_out_time'=>$CHECK_OUT_TIME]);
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
            $billing_data = CheckIn::with(['additionalServices','additionalServices.billingRules'])->whereIn('id',$checkInIds )->get()->toArray();
            
           //calling function from check out controller to calculate bill of current checkout
            $finalCharges = CheckOutController::property_billing($check_in_data, $billing_data,$guest_and_room_count,$userID,$CHECK_OUT_DATE , $CHECK_OUT_TIME, $request['check_out_type']);
            DB::table('property_billings')->insert($finalCharges['billing']);
            DB::table('payables')->insert($finalCharges['payable']);
            DB::table('room_lists')->whereIn('id', $room_ids)->where('room_status', 'occupied')->update(['room_status' => 'available', 'updated_by' => $userID->id, 'check_in_date' => null, 'check_out_date' => null]);
            $check_in_controller = app(CheckInController::class);
            $response = null;
            if($request['check_out_type'] === 'partial'){
                $response = $check_in_controller->checkin_from_existing_data($check_in_data, $CHECK_OUT_DATE, $CHECK_OUT_TIME, $userID);
            }
            else if($request['check_out_type'] === 'relocate'){
                $response = $check_in_controller->relocate($check_in_data, $CHECK_OUT_DATE, $CHECK_OUT_TIME, $userID);
            }
            else{
                $response = "OK";//in case of full checkout response is OK
            }
            if($response === "OK"){
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
}