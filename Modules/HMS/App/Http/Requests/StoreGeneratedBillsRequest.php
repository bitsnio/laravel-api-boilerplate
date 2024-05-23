<?php

namespace Modules\HMS\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGeneratedBillsRequest extends StoreValidationErrorRequest
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
    public function rules(): array{
        return [

            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'property_id'=> 'integer|nullable|sometimes',
            'checkin_ids'=> 'array|sometimes'

        ];
    }
}
