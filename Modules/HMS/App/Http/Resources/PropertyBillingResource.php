<?php

namespace Modules\HMS\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyBillingResource extends BaseResource
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
        $default ['property_billings'] = BaseResource::collection($default ['property_billings']);
        foreach($default['property_billings'] as $billing){
            $billing['days'] = abs($billing['days']);
            unset($billing['property_id']);
            unset($billing['check_in_id']);
            unset($billing['assigned_additional_service_id']);
        }
        return $default;
    }
}
