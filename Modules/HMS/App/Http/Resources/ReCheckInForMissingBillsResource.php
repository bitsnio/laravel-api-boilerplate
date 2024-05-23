<?php

namespace Modules\HMS\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HMS\App\Http\Resources\CheckedInMemberResource;

class ReCheckInForMissingBillsResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        dd(11);
        $default = parent::toArray($request);
        $default['selected_services'] = $default['id'];
        unset($default['id']);
        $default['guests'] = BaseResource::collection($this->guests);
        return $default;
    }
}
