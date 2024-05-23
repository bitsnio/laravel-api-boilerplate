<?php
namespace Modules\HMS\App\Utilities;

use Modules\HMS\App\Models\PropertySetting;
use Modules\HMS\App\Models\RoleWithPermission;
use App\Models\User;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\DB;
use Throwable;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class Helper{

    public static function successResponse($data, $message = null, $code = 200,$token=null){
        return response()->json([
			'success'=> true, 
			'message' => $message, 
			'data' => $data,
            'token'=>$token
		], $code);
    }

    public static function errorResponse($message = null, $code=400,$token=null){
        return response()->json([
			'success'=>false,
			'message' => $message,
			'data' => null,
            'token'=>$token
		], $code);
    }

    public static function validationErrorsToString($errArray){
        $valArr = array();
        foreach ($errArray->toArray() as $key => $value) { 
            $errStr = $key.':'.$value[0];
            array_push($valArr, $errStr);
        }
        if(!empty($valArr)){
            $errStrFinal = implode('<br>', $valArr);
        }
        return $errStrFinal;
    }

    public static function usersModules($id){
        $user = User::where('users.id', $id)->select('role', 'company_id','name','id')->first();
        $main_modules = DB::table('users')
        ->join(
            'main_modules', 
            DB::raw("FIND_IN_SET(main_modules.id,users.main_module_id)"),">",DB::raw("'0'"))
        ->leftJoin(
            'sub_modules', 
            'sub_modules.main_module_id', '=', 'main_modules.id')
        ->select(
            'main_modules.slug as MM',
            'main_modules.icon as MM_ICON',
            'main_modules.title as M_title', 
            'main_modules.route as M_route', 
            'users.name',
            'users.id',
            'sub_modules.slug as Sub_slug',
            'sub_modules.title as sub_title', 
            'sub_modules.route as sub_route',
            'sub_modules.menu_order as sub_menu_order',
            'sub_modules.icon as sub_icon',
            )
        ->where('users.id', '=', $id)
        // ->whereNotExists(function ($query) use ($user) {
        //     $query->select(DB::raw(1))
        //         ->from('user_roles')
        //         ->whereRaw('FIND_IN_SET(sub_modules.id, user_roles.sub_module_id)')
        //         ->where('user_roles.id', '=' ,$user->role)
        //         ->where('user_roles.company_id', '=' ,$user->company_id);
        // })
        ->orderBy('main_modules.slug')
        ->orderBy('sub_modules.menu_order')
        ->get();

        //get permissions assign to a user role
        // $permissions = RoleWithPermission::where('role_id', $user['role'])
        // ->Join('permissions', 'role_with_permissions.role_id', '=', 'permissions.id')
        // ->select('permission_title', 'permission', 'route')
        // ->get()->toArray();

        $result['menu'] = self::convertToChildrens($main_modules);
        $result['restricted_permissions'] = [];
        $result['user_info'] = array("user_name"=>$user->name,"id"=>$user->id);
     
        return $result;
    }

    private static function convertToChildrens($inputArray){
        $resultArray = [];
        $i = 0;
        $mmKey = '';
        $firstIteration = true;
        $value = true;
        foreach ($inputArray as $item) {
           
            if($mmKey != $item->MM){
                if(!$firstIteration) $i++;

                $resultArray[$i] = [
                    'title' => $item->M_title,
                    'path' =>$item->M_route.$item->MM,
                    'collapse'=>$item->MM,
                    'icontype'=>$item->MM_ICON,
                    'isCollapsed'=>false,
                    'type'=>'sub',
                    'children' =>[]
                ];
              
                // $resultArray[$i]['type'] = ($item->Sub_slug != null)?'sub':'link';
            }
            
            $resultArray[$i]['children'][] = [
                'type' => 'link',
                'title' => $item->sub_title,
                'path' => $item->sub_route,
                'menu_order' => $item->sub_menu_order,
                'icontype' => $item->sub_icon,
                // 'i'=>$i
                
            ];
         
            $mmKey = $item->MM;
            $firstIteration = false;
        }
        
        return $resultArray;
    }

    public static function objectsToArray($objects, $additionalFields) {
        return array_map(function($object) use ($additionalFields) {
            // Convert the object to an associative array
            $array = (array) $object;
            // Merge additional fields with the array
            return array_merge($array, $additionalFields);
        }, $objects);
    }

    public static function unsetFields($array){
        $userID = JWTAuth::parseToken()->authenticate();
        $updated_records = array_map(function ($record) use ($userID) {
            unset($record['id']);
            unset($record['created_by']);
            unset($record['updated_by']);
            unset($record['deleted_by']);
            unset($record['is_deleted']);
            unset($record['created_at']);
            unset($record['updated_at']);
            $record['created_by'] = $userID->id;
            return $record;
        }, $array);
        return $updated_records;
    }
    public static function calculateDaysNights($checkInDate, $checkInTime, $checkOutDate, $checkOutTime, $property_id){
        try{
            // $checkInDate = "2023-10-01"; 
            // $checkInTime = "09:00:00";
            // $checkOutDate = "2023-10-10"; 
            // $checkOutTime = "05:00:00";
            $checkInTime = date("H:i:s", strtotime($checkInTime));
            $checkOutTime = date("H:i:s", strtotime($checkOutTime));
            $checkInDate = Carbon::parse($checkInDate);
            $checkOutDate = Carbon::parse($checkOutDate);
            $days = $checkInDate->diffInDays($checkOutDate);
            $stayDuration = $days;
            $partialDuration = $days;
            $days--;
            $day_count = $days;
            $night_count = $days;
            $day_count_rule = PropertySetting::where('property_id', $property_id)->where('key', 'day_start_time')->first();
            if($day_count_rule == null){
                return ['error' => 'Settings not found for day start time'];
            }
            $day_start_time = date("H:i:s", strtotime($day_count_rule['value']));
            $night_start_time = date("H:i:s", strtotime($day_start_time.'+12 hours'));
            // $day_start_time = date("H:i:s", strtotime("05:00:00"));// will be removed when set day start time time in property settings
            // $night_start_time = date("H:i:s", strtotime("17:00:00"));// will be removed when set night start time in property settings
            if($checkInTime >= $night_start_time || $checkInTime < $day_start_time ){
                $night_count++;
            }else{
                $day_count++;
                $night_count++;
            }
            if($checkOutTime >= $night_start_time){
                $day_count++;
                $night_count++;
            }else{
                if($checkOutTime >= $day_start_time ){
                    $day_count++;
                }
            }
            // count room stay duration on basis of next day room_charge_time from property settings table
            $room_charge_rule = PropertySetting::where('property_id', $property_id)->where('key', 'room_charge_time')->first();
            if($room_charge_rule == null){
                return ['error' => 'Settings not found for room charge time'];
            }
            $charge_time =  date("H:i:s", strtotime($room_charge_rule['value']));
            // $charge_time = '17:00:00';// manual time will be removed when property settings will be enabled
            if($checkOutTime >= $charge_time){
                $stayDuration++;
            }
            // dd($day_count, $night_count, $stayDuration);
            return ['days' => $day_count, 'nights' => $night_count, 'stay_duration' => $stayDuration, 'partial_duration' => $partialDuration];
        }
        catch(Throwable $th){
            return ['error' => $th->getMessage()];
        }
    }

    //function to trim array spaces
    public static function trimArray($array){
        try{
            if(!is_array($array) && count($array) == 1){
                return ['error' => 'given input must be a valid array'];
            }
            $data = [];
            foreach($array as $index => $value){
                if($value == null){
                    $data[$index] = $value;
                } else{
                    $data[$index] = trim($value);
                }
            }
            return $data;
        } catch(Throwable $th){
            return ['error' => $th->getMessage()];
        }
    }

    public static function findDuplicates($records, $key){
        try{
            $duplicates = [];
            $uniqueValues = [];
            foreach ($records as $record) {
                $value = $record[$key];
                // Check if the value is already in the uniqueValues array
                if (in_array($value, $uniqueValues)) {
                    // If yes, it's a duplicate; add it to the duplicates array
                    $rec = [];
                    $rec[$key] = $record[$key];
                    $rec['registeration_number'] = $record['registeration_number'];
                    $rec['family_name'] = $record['family_name'];
                    $duplicates[] = $rec;
                } else {
                    // If not, add it to the uniqueValues array
                    $uniqueValues[] = $value;
                }
            }
            return $duplicates;
        } 
        catch(Throwable $th){
            return['error' => $th->getMessage()];
        }
    }

    public static function ValidateSettings($attribute, $value, $settings){
        try{
            $options = collect($settings)->where('key', $attribute)->pluck('value')->toArray();
            // dd($options, $attribute ,$value, $settings);
            if(!in_array(strtolower($value), array_map('strtolower',$options))){
                return ['fail' => 'Invalid '.$attribute.' selected, only ('. implode(', ', $options).') are allowed'];
            }
        }
        catch(Throwable $th){
            return ['fail' => $th->getMessage()];
        }
    }

    public static function isValidTime($time){
        try{
            $format = 'h:i A';
            // Create a DateTime object using the specified format
            $dateTime = DateTime::createFromFormat($format, $time);
            // Check if the DateTime object was created successfully and the input matches the format
            return $dateTime && $dateTime->format($format) == $time;
        }
        catch(Throwable $th){
            return ['error' => $th->getMessage()];
        }
    }
}
?>