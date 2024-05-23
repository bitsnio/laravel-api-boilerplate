<?php

namespace Modules\HMS\App\Http\Resources;

use App\Models\CheckIn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyGetResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $default =  parent::toArray($request);
        
        $data = $default['additional_service_ids']['additional_services'];
        unset($default['additional_service_ids']);
        $default['additional_service'] = $data;
        // dd($data);
        return $default;
    }
}
