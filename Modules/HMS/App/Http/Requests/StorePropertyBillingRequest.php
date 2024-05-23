<?php

namespace Modules\HMS\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyBillingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'property_id' => 'required', 
            'check_in_id' => 'required',
            'item_name' => 'required',
            'selling_price' => 'required',
            'assigned_additional_service_id' => 'integer',
            'quantity' => 'required',
            'total_amount' => 'required',
            'days'=>'required|integer',
            'uom'=>'required'
        ];
    }
}
