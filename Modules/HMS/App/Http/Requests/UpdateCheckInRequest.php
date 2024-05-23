<?php

namespace Modules\HMS\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCheckInRequest extends StoreValidationErrorRequest
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
            'property_id' => 'required|integer',
            'registeration_number' => 'required',
            'family_name' => 'required',
            'check_in_type' => 'required|string',
            'total_persons' => 'required|integer|min:1',
            'bound_country' => 'required',
            'selected_services' => 'required|string',
            'payment_type' => 'required|string',
            'check_in_date' => 'required|date',
            'check_in_time' => 'required|date_format:h:i A',
            'expected_check_out_date' => 'nullable|sometimes|date',
            'expected_check_out_time' => 'nullable|sometimes|date_format:h:i A',
            'selected_services' => 'required|string',
            'guestDetails' => 'nullable|sometimes|array|min:1',
            'guestDetails.*.guest_name' => 'required|string',
            // 'guestDetails.*.room_number' => 'required|integer|min:1',
            'guestDetails.*.room_number' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) {
                    $duplicates = collect($this->input('guestDetails'))
                        ->pluck('room_number')
                        ->duplicates()
                        ->all();
                    if (in_array($value, $duplicates)) {
                        if (strtolower($this->input('check_in_type')) == 'event') {
                            $fail("Two events cannot be organized in same Room/Hall simultaneously");
                        }
                    }
                },
            ],
            'guestDetails.*.date_of_birth' => 'required|date|before_or_equal:check_in_date',
            'guestDetails.*.visa_expiry' => 'nullable|sometimes|date|after_or_equal:check_in_date',
            'guestDetails.*.cnic_passport_number' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $duplicates = collect($this->input('guestDetails'))
                        ->pluck('cnic_passport_number')
                        ->duplicates()
                        ->all();
                    if (in_array($value, $duplicates)) {
                        if (strtolower($this->input('check_in_type')) != 'event') {
                            $fail("The cnic-passport number must be unique for each guest, duplicate cnic-passport: $value found.");
                        }
                    }
                },
            ]
        ];
    }
    public function messages(){
        return [
            'guestDetails.*.guest_name.required' => 'Guest name field is required.',
            'guestDetails.*.guest_name.string' => 'The guest name must be a string.',
            'guestDetails.*.room_number.required' => 'Room number field is required.',
            'guestDetails.*.room_number.integer' => 'The room number must be an integer.',
            'guestDetails.*.room_number.min' => 'The room number must be at least 1.',
            'guestDetails.*.date_of_birth.required' => 'Date of birth field is required.',
            'guestDetails.*.date_of_birth.date' => 'The date of birth must be a valid date.',
            'guestDetails.*.date_of_birth.before_or_equal' => 'The date of birth must be before or equal to the check-in date.',
            'guestDetails.*.visa_expiry.date' => 'The visa expiry date must be a valid date.',
            'guestDetails.*.visa_expiry.after_or_equal' => 'The visa expiry date must be after or equal to the check-in date.',
            'guestDetails.*.cnic_passport_number.required' => 'cnic-passport number field is required.',
            'guestDetails.*.cnic_passport_number.string' => 'The cnic-passport number must be a string.',
            'guestDetails.*.cnic_passport_number.unique' => 'TThe cnic-passport number must be unique for each guest, duplicate cnic-passport: :input found.',
        ];
    }
}
