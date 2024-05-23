<?php

namespace Modules\HMS\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckedInMemberRequest extends StoreValidationErrorRequest
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
            'check_in_id' => 'required|integer',
            'check_in_date' => 'required|date',
            'guests' => 'required|array|min:1',
            'guests.*.guest_name' => 'required|string',
            'guests.*.room_number' => [
                'required',
                function ($attribute, $value, $fail) {
                        if ($this->input('check_in_type') == 'Event') {
                            if (!is_string($value)) {
                                $fail("Room/Hall number must be a string");
                            }
                            $room_array = explode(',', $value);
                            $duplicate_room  = collect($room_array)->duplicates()->toArray();
                            if(!empty($duplicate_room)){
                                $fail("Two or more events cannot be organized in same Room/Hall simultaneously");
                            }
                        }
                        else{
                            if (!is_numeric($value)) {
                                $fail("Room/Hall must be an integer");
                            }
                            $duplicates = collect($this->input('guests'))
                            ->pluck('room_number')
                            ->duplicates()
                            ->all();
                            if (in_array($value, $duplicates)) {
                                if ($this->input('check_in_type') == 'Event') {
                                    $fail("Two or more events cannot be organized in same Room/Hall simultaneously");
                                }
                            }
                        }
                },
            ],
            'guests.*.date_of_birth' => 'required|date|before_or_equal:check_in_date',
            'guests.*.visa_expiry' => 'nullable|sometimes|date|after_or_equal:check_in_date',
            // 'guests.*.cnic_passport_number' => 'required|string|unique:checked_in_members,cnic_passport_number,NULL,id,check_in_id,'.$this->check_in_id,
            'guests.*.cnic_passport_number' => [
                'required',
                'string',
                'unique:checked_in_members,cnic_passport_number,NULL,id,check_in_id,'.$this->check_in_id,
                function ($attribute, $value, $fail) {
                    $duplicates = collect($this->input('guests'))
                        ->pluck('cnic_passport_number')
                        ->duplicates()
                        ->all();
                    if (in_array($value, $duplicates)) {
                        if ($this->input('check_in_type') != 'Event') {
                            $fail("The cnic-passport number must be unique for each guest, duplicate cnic-passport: $value found.");
                        }
                    }
                },
            ]
        ];
    }
    public function messages(){
        return [
            'guests.*.guest_name.required' => 'Guest name field is required.',
            'guests.*.guest_name.string' => 'The guest name must be a string.',
            'guests.*.room_number.required' => 'Room number field is required.',
            'guests.*.date_of_birth.required' => 'Date of birth field is required.',
            'guests.*.date_of_birth.date' => 'The date of birth must be a valid date.',
            'guests.*.date_of_birth.before_or_equal' => 'The date of birth must be before or equal to the check-in date.',
            'guests.*.visa_expiry.date' => 'The visa expiry date must be a valid date.',
            'guests.*.visa_expiry.after_or_equal' => 'The visa expiry date must be after or equal to the check-in date.',
            'guests.*.cnic_passport_number.required' => 'cnic-passport number field is required.',
            'guests.*.cnic_passport_number.string' => 'The cnic-passport number must be a string.',
            'guests.*.cnic_passport_number.unique' => 'The cnic-passport number: :input already taken.',
        ];
    }
    
}
