<?php

namespace Modules\HMS\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        $data = parent::toArray($request);

        // Hide common fields
        // unset($data['company_id']);
        unset($data['created_by']);
        unset($data['updated_by']);
        unset($data['deleted_by']);
        unset($data['created_at']);
        unset($data['updated_at']);
        return $data;
    }
}
