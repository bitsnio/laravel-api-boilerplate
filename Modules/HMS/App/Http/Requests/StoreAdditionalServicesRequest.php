<?php

namespace Modules\HMS\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdditionalServicesRequest extends StoreValidationErrorRequest
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
            // 'property_id' => 'required',
            'basis_of_application' => 'required|string',
            'frequency' => 'required|string',
            'service_name' => 'required|string',
            'cost' => 'required|decimal:0,2|gte:0',
            'selling_price' => 'required|decimal:0,2|gte:0',
        ];
    }
}
