<?php

namespace Modules\HMS\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillingtimeRuleResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array{
        return [
            // "additional_service_id" => $this->additional_service_id,
            "id" => $this->id,
            "title" => $this->title,
            "from" => $this->from,
            "to" => $this->to,
            "charge_compare_with" => $this->charge_compare_with,
            "charge_percentage"=> $this->charge_percentage,
            "apply_on"=> $this->apply_on,
        ];
    }
}
