<?php

namespace Modules\HMS\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReceiptRequest extends StoreValidationErrorRequest
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
            'property_id' => 'nullable|integer|sometimes',
            'check_in_ids' => 'required|array|min:1',
            'receipt_type' => 'required|string',
            'selected_id' => 'array|sometimes|nullable',
            'client_id' => 'required|integer',
        ];
    }
}
