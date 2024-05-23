<?php

namespace Modules\HMS\App\Http\Requests;

use App\Core\Memmory;
use Modules\HMS\App\Utilities\Helper;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends StoreValidationErrorRequest
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
        $settings = Memmory::propertySettings();
        return [
            'reference_number' => 'nullable|string',
            'property_id' => 'required|integer',
            'paid_amount' => 'required|numeric|lt:'.$this->total_amount,
            'total_amount' => 'required|numeric|gte:'.$this->paid_amount + $this->processed_amount,
            'payment_method' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($settings) {
                    $validation = Helper::ValidateSettings('payment_method', $value, $settings);
                    if(isset($validation['fail'])) $fail($validation['fail']);
                }
            ],
            'processed_amount' => 'required|numeric|gt:0|lte:'.$this->total_amount - $this->paid_amount,
            'payment_date' => 'required|date',
            'receipt_id' => 'required|integer',
        ];
    }
    public function messages(){
        return [
            'total_amount.gte' => 'Processed amount cannot be greater than payable balance.',
            'paid_amount.lt' => 'Already paid.',
            'processed_amount.gt' => 'Processed amount must be greater than 0.',
            'processed_amount.lte' => 'Processed amount cannot be greater than payable balance.',
        ];
    }
}
