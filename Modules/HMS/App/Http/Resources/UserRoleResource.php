<?php

namespace Modules\HMS\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserRoleResource extends BaseResource
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

        // Remove the company_id attribute
        unset($data['company_id']);

        return $data;
    }
}
