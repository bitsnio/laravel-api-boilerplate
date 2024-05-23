<?php

namespace Modules\HMS\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HMS\App\Http\Resources\BillingtimeRuleResource;

class AdditionalServicesResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $default =  parent::toArray($request);
        unset($default['company_id']);
        $default ['billing_rules'] = BillingtimeRuleResource::collection($this->billingRules);
        return $default;
    }
}
