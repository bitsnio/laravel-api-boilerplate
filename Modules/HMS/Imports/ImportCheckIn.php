<?php

namespace App\Imports;

use App\Core\Memmory;
use Modules\HMS\App\Http\Controllers\API\CheckInController;
use Modules\HMS\App\Models\AdditionalServices;
use Modules\HMS\App\Models\AssignedAdditionalServices;
use Modules\HMS\App\Models\BillingTimeRule;
use Modules\HMS\App\Models\CheckIn;
use Modules\HMS\App\Models\Property;
use Modules\HMS\App\Models\PropertyAdditionalServicesRelation;
use Modules\HMS\App\Models\PropertyServices;
use Modules\HMS\App\Models\PropertySetting;
use Modules\HMS\App\Models\RoomList;
use Modules\HMS\App\Utilities\Helper;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithValidation;
use Throwable;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class ImportCheckIn implements ToCollection, SkipsEmptyRows, WithHeadingRow, WithValidation
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function collection(Collection $row)
    {
        DB::beginTransaction();
        // try{
        $array_records = collect($row)->toArray();
        $duplicate_records = Helper::findDuplicates($array_records,'cnic_passport_number');
        if (!empty($duplicate_records)) {
            $errors = implode(', ', array_map(function ($record) {
                return $record['registeration_number'] . ' ' . $record['family_name'];
            }, $duplicate_records));
            throw new \Exception("Duplicate cnic/passport number found against family ".$errors);
        }
        $data = collect($row)->unique('cnic_passport_number')->groupBy('registeration_number')->toArray();
        $user = JWTAuth::parseToken()->authenticate();
        $properties = Property::where('company_id', $user->company_id)->get(['id', 'property_name'])->toArray();
        foreach($data as $guests){
            $checkIn = Helper::trimArray($guests[0]);
            $id = collect($properties)->where('property_name', $checkIn['property_name'])->pluck('id')->toArray();
            if(empty($id)){
                throw new \Exception('property '.$checkIn['property_name'].' does not exist');
            }
            $check_in_data = [];
            $guest_data = [];
            $guest_data = [];
            $check_in_data['property_id'] = $id[0];
            $check_in_data['registeration_number'] = $checkIn['registeration_number'];
            $check_in_data['family_name'] = $checkIn['family_name'];
            $check_in_data['check_in_type'] = strtolower($checkIn['check_in_type']);
            $check_in_data['total_persons'] = $checkIn['total_persons'];
            $services = array_map('trim',explode(",", $checkIn['selected_services']));
            $selected_services = PropertyServices::whereIn('service_name', $services)->where('property_id',  $id[0])->get()->pluck('id')->toArray();
            // todo
            sort($selected_services);
            $check_in_data['selected_services'] = implode(',', $selected_services);
            $check_in_data['bound_country'] = $checkIn['bound_country'];
            $check_in_data['total_persons'] = count($guests);
            $check_in_data['payment_type'] = strtolower($checkIn['payment_type']);
            $check_in_data['check_in_date'] = $checkIn['check_in_date'];
            $check_in_data['check_in_time'] = $checkIn['check_in_time'];
            $check_in_data['expected_check_out_date'] = $checkIn['expected_check_out_date'];
            $check_in_data['expected_check_out_time'] = $checkIn['expected_check_out_time'];
            foreach($guests as $guest){
                $guest = Helper::trimArray($guest);
                $g_d['property_id'] = $id[0];
                $g_d['guest_name'] = $guest['guest_name'];
                $g_d['date_of_birth'] = $guest['date_of_birth'];
                $room_id = RoomList::where('room_number', $guest['room_number'])->where('property_id', $id[0])->first();
                if($room_id == null){
                    throw new \Exception('room '.$guest['room_number'].' not found');
                }
                $g_d['room_number'] = $room_id->id;
                $g_d['customer_type'] = $guest['customer_type'];
                $g_d['cnic_passport_number'] = $guest['cnic_passport_number'];
                $g_d['visa_expiry'] = $guest['visa_expiry'];
                $g_d['customer_city'] = $guest['customer_city'];
                $g_d['customer_province'] = $guest['customer_province'];
                $g_d['customer_postal_code'] = $guest['customer_postal_code'];
                $g_d['customer_home_address'] = $guest['customer_home_address'];
                $guest_data[] = $g_d;
            }
            $checkin_payload = $check_in_data;
            $checkin_payload['guestDetails'] = $guest_data;
            $checkin_function = app(CheckInController::class)->checkIn($checkin_payload);
            if(isset($checkin_function['error'])){
                DB::rollBack();
                throw new \Exception($checkin_function['error']);
            }
        }
        DB::commit();
    }

    //Applying valdation rules
    use Importable;
    public function rules(): array
    {
        $settings = Memmory::propertySettings();
        return [
            'property_name' => [
                'required',
                'string',
            ],
            'payment_type' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($settings) {
                    $validation = Helper::ValidateSettings('payment_type', $value, $settings);
                    if(isset($validation['fail'])) $fail($validation['fail']);
                }
            ],
            'registeration_number' => [
                'required',
                'string',
            ],
            'family_name' => [
                'required',
                'string',
            ],
            'check_in_type' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($settings) {
                    $validation = Helper::ValidateSettings('checkin_type', $value, $settings);
                    if(isset($validation['fail'])) $fail($validation['fail']);
                }
            ],
            'bound_country' => [
                'required',
                'string',
            ],
            'selected_services' => [
                'required',
                'string',
            ],
            'check_in_date' => [
                'required',
                'string',
            ],
            'check_in_time' => [
                'required',
                'string',
            ],
            'expected_check_out_date' => [
                'nullable',
                'sometimes',
                'string',
            ],
            'expected_check_out_time' => [
                'nullable',
                'sometimes',
                'string',
            ],
            'guest_name' => [
                'required',
                'string',
            ],
            'date_of_birth' => [
                'required',
                'string',
                // 'before_or_equal:check_in_date',
            ],
            'room_number' => [
                'required',
                'string',
            ],
            'cnic_passport_number' => [
                'required'
            ],
            'visa_expiry' => [
                'nullable',
                'sometimes',
                'string',
                // 'after_or_equal:check_in_date',
            ],
        ];
    }

    public function services($services, $user, $property_id, $check_in_id){
        try{
            $services = array_map('trim',explode(",", $services));
            $record = AdditionalServices::whereIn('service_name', $services)->where('company_id', $user->company_id)->get('id')->toArray();
            $record = collect($record)->pluck('id')->toArray();
            $selected_services = PropertyAdditionalServicesRelation::whereIn('additional_service_id',$record)->where('property_id', $property_id)->get('id')->toArray();
            $selected_services = collect($selected_services)->pluck('id')->toArray();
            sort($selected_services);
            foreach($selected_services as $s_services){
                $additional_services = AdditionalServices::find($s_services,['id', 'service_name', 'basis_of_application', 'frequency', 'cost', 'selling_price'])->toArray();
                $additional_services['created_by'] = $user->id;
                $additional_services['check_in_id'] = $check_in_id;
                $additional_services['property_id'] = $property_id;
                $billing_time_rules = BillingTimeRule::where('additional_service_id', $s_services)->get(['title', 'from', 'to', 'charge_compare_with', 'charge_percentage'])->toArray();
                $a_id = AssignedAdditionalServices::create($additional_services);
                $additional_fields_AS =  ['assigned_additional_service_id'=>$a_id->id, 'created_by'=>$user->id];
                $ABR = Helper::objectsToArray($billing_time_rules, $additional_fields_AS);
                // dd($ABR) ;
                DB::table('assigned_billing_time_rules')->insert($ABR);
            }
            // dd($selected_services);
        }
        catch(Throwable $th){
            return $th->getMessage();
        }
    }
}