<?php

namespace Modules\HMS\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdditionalServicesRequest extends StoreAdditionalServicesRequest {
    public function authorize(): bool {
        return true;
    }

    /**
    * Get the validation rules that apply to the request.
    *
    * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
    */

    public function rules(): array {
        return [
            // 'company_id' => 'required|integer',
            // 'service_name' => 'required|string',
            // 'basis_of_application' => 'required|string',
            // 'frequency' => 'required|string',
            // 'cost' => 'required',
            // 'selling_price' => 'required',
        ];
    }
}
