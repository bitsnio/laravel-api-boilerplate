<?php

namespace Modules\HMS\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckedInMemberResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id"=>$this->id,
            "guest_name"=>$this->guest_name,
            "date_of_birth"=>$this->date_of_birth,
            "room_number"=>$this->roomDetails,
            "cnic_passport_number"=>$this->cnic_passport_number,
            "visa_expiry"=>$this->visa_expiry
        ];
    }
}
