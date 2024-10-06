<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomFormRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "propeerty_id" => 'required|integer',
            "check_in_id" => 'required|integer',
            "guest_name" => 'required|string',
            "guest.*id" => 'required|integer',
            "guest.*name" => 'required|string',
            "date_of_birth" => 'required|string',
            "room_number" => 'required|integer',
            "cnic_passport_number" => 'required|string',
        ];
    }
}
