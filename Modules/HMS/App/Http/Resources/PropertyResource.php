<?php

namespace Modules\HMS\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'property_name' => $this->property_name,
            'category' => $this->category,
            'property_type' => $this->property_type,
            'property_email' => $this->property_email,
            'property_phone' => $this->property_phone,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'street_address' => $this->street_address,
            'description' => $this->description ,
            'additional_services' => $this->mapPropertyServices($this->propertyServices),
        ];
    }

    protected function mapPropertyServices($propertyServices)
    {
        return collect($propertyServices)->map(function ($service) {
            return collect($service)->except('property_id')->all();
        });
    }
}
