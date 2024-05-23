<?php

namespace Modules\HMS\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyAdditionalServicesRelationResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $default = parent::toArray($request);
        unset($default['id']);
        unset($default['property_id']);
        unset($default['additional_service_id']);
        return $default;
    }
}
