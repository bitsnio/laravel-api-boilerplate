<?php

namespace Modules\HMS\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomtypeResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $default = parent::toArray($request);
        $default['property_id'] = $this->property;
        unset($default['property']);
        return $default;
    }
}
