<?php

namespace Modules\HMS\App\Http\Requests;

use Modules\HMS\App\Utilities\Helper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StorePropertyRequest extends StoreValidationErrorRequest
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
            'category' => 'required',
            'additional_services' => 'required',
            'property_name' => 'required',
            'property_type' => 'required',
            'property_email' => 'required|email|unique:properties,property_email',
            'property_phone' => 'required',
            'city' => 'required',
            'postal_code' => 'required',
            'street_address' => 'required',
            'description' => 'nullable',
        ];
    }
}
