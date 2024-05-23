<?php

namespace Modules\HMS\App\Http\Resources;

use App\Models\CheckIn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayableResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $default =  parent::toArray($request);
        unset($default['property_id']);
        unset($default['check_in_type']);
        unset($default['booking_notes']);
        unset($default['payment_type']);
        unset($default['selected_services']);
        unset($default['expected_check_out_date']);
        unset($default['expected_check_out_time']);
        unset($default['last_check_in_id']);
        unset($default['check_in_status']);
        // unset($default['check_in_date']);
        // unset($default['check_in_time']);
        // unset($default['check_out_date']);
        // unset($default['check_out_time']);
        $default['category'] = $default['property']['category'];
        unset($default['property']['category']);
        $default['payables'] = BaseResource::collection($default['payables']);
        foreach($default['payables'] as $payable){
            $payable['days'] = abs($payable['days']);
            unset($payable['property_id']);
            unset($payable['check_in_id']);
            unset($payable['assigned_additional_service_id']);
        }
        return $default;
    }
}
