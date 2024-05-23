<?php

namespace Modules\HMS\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBillingTimeRuleRequest extends StoreValidationErrorRequest
{
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
            'title' => 'string',
            'charge_compare_with' => 'string',
            'from' => 'required',
            'to' => 'required',
            'charge_percentage' => 'required',
            'apply_on' => 'string'
        ];
    }
}