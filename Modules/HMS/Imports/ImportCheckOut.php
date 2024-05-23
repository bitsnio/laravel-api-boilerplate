<?php

namespace App\Imports;

use Modules\HMS\App\Http\Controllers\API\CheckInController;
use Modules\HMS\App\Http\Controllers\API\CheckOutController;
use Modules\HMS\App\Models\AdditionalServices;
use Modules\HMS\App\Models\AssignedAdditionalServices;
use Modules\HMS\App\Models\BillingTimeRule;
use Modules\HMS\App\Models\CheckIn;
use Modules\HMS\App\Models\Property;
use Modules\HMS\App\Models\PropertyAdditionalServicesRelation;
use Modules\HMS\App\Models\PropertyServices;
use Modules\HMS\App\Models\RoomList;
use App\Traits\PaymentDataCreater;
use Modules\HMS\App\Utilities\Helper;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithValidation;
use Throwable;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class ImportCheckOut implements ToCollection, SkipsEmptyRows, WithHeadingRow, WithValidation
{
    use PaymentDataCreater;

    public function collection(Collection $row)
    {
        DB::beginTransaction();
        $duplicate_records = Helper::findDuplicates($row->toArray(),'check_in_id');
        if (!empty($duplicate_records)) {
            $errors = implode(', ', array_map(function ($record) {
                return $record['registeration_number'] . ' ' . $record['family_name'];
            }, $duplicate_records));
            throw new \Exception("Duplicate check_ins found against family ".$errors);
        }
        $user = JWTAuth::parseToken()->authenticate();
        foreach($row->toArray() as $record){
            $property = Property::where('company_id', $user->company_id)->where('id', $record['property_id'])->first();
            if($property == null){
                throw new \Exception("Invalid property id/name found against ".$record['property_name']."-".$record['registeration_number']."-".$record['family_name']);
            }
            $payload = [];
            $payload['check_out_date'] = $record['check_out_date'];
            $payload['check_out_time'] = $record['check_out_time'];
            $payload['check_in_data'][] = ['check_in_id' => $record['check_in_id'], 'guests' => null];
            $this->process_checkout($payload,[],['type' => 'partial']);            
        }
        DB::commit();
    }

    //Applying valdation rules
    use Importable;
    public function rules(): array
    {
        return [
            'property_id' => [
                'required',
                'integer',
            ],
            'registeration_number' => [
                'required',
                'string',
            ],
            'check_out_date' => [
                'nullable',
                'sometimes',
                'string',
            ],
            'check_out_time' => [
                'nullable',
                'sometimes',
                'string',
            ],
        ];
    }
}