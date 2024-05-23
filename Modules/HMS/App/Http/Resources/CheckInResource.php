<?php

namespace Modules\HMS\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HMS\App\Http\Resources\CheckedInMemberResource;

class CheckInResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $default = parent::toArray($request);
        unset($default['last_check_in_id']);
        unset($default['selected_services']);
        unset($default['parent_id']);
        unset($default['property_id']);
        $default['check_in_time'] = date("h:i A", strtotime($default['check_in_time']));
        if($default['check_in_status'] == 'checked_out'){
            $default['check_out_time'] = date("h:i A", strtotime($default['check_out_time']));
        }
        if(isset($default['expected_check_out_time']) && $default['expected_check_out_time'] != null){
            $default['expected_check_out_time'] = date("h:i A", strtotime($default['expected_check_out_time']));
        }
        $default['family_rooms'] = collect($default['guests'])->pluck('room_number')->unique()->count();
        $default['guests'] = CheckedInMemberResource::collection($this->guests);
        return $default;
    }
}
