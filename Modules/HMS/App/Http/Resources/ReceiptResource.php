<?php

namespace Modules\HMS\App\Http\Resources;

use App\Models\CheckIn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceiptResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request):array
    {
        $default = parent::toArray($request);
        $default['total_amount'] = $default['total_merged_amount'];
        $default['payment_status'] = 'partial';
        unset($default['total_merged_amount']);
        unset($default['property_id']);
        if(empty($default['payments'])){
            $default['paid_amount'] = 0;
        }
        else{
            $default['paid_amount'] = collect($default['payments'])->sum('paid_amount');
        }
        if($default['total_amount'] == $default['paid_amount']){
            $default['payment_status'] = 'paid';
        }
        $default['payments'] = PaymentResource::collection($default['payments']);
        return $default;
    }
}
