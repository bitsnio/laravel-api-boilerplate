<?php

namespace Modules\HMS\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomTypeRequest extends StoreValidationErrorRequest
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
            'room_type' => 'required|string',
            'rental_price' => 'required|integer',
            'hiring_cost' => 'required|integer',
            'quantity' => 'required|integer',
            'room_number_start_from' => 'required|integer',
            'room_prefix'=>'required|string'
        ];
    }
}
