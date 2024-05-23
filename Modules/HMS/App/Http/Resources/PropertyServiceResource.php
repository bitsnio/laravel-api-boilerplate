<?php

namespace Modules\HMS\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyServiceResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $default = parent::toArray($request);
        unset($default['property_id']);
        $default ['property_service_rules'] = PropertyServiceRulesResource::collection($default ['property_service_rules']);
        return $default;
    }
}
