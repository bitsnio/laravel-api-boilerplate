<?php

namespace Modules\HMS\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCheckedInMemberRequest extends StoreValidationErrorRequest
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
            'check_in_id' => 'integer',
            'property_id' => 'integer',
            'guest_name' => 'string',
            'date_of_birth' => 'date',
            'cnic_passport_number' => 'string',
            'visa_expiry' => 'nullable|sometimes|date',
        ];
    }
}
