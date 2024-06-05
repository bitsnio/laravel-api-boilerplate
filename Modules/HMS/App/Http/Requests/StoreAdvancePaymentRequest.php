<?php

namespace Modules\HMS\App\Http\Requests;

use Modules\HMS\Core\Memmory;
use Modules\HMS\App\Utilities\Helper;
use Illuminate\Foundation\Http\FormRequest;

class StoreAdvancePaymentRequest extends StoreValidationErrorRequest {
    /**
    * Determine if the user is authorized to make this request.
    */

    public function authorize(): bool {
        return true;
    }

    /**
    * Get the validation rules that apply to the request.
    *
    * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
    */

    public function rules(): array {
        $settings = Memmory::propertySettings();
        return [
            'property_id' => 'required|integer',
            'advance_amount' => 'required|decimal:0,2|gt:0',
            'payment_date' => 'required|date',
            'payment_reference' => 'nullable|string|sometimes',
            'payment_method' => [
                'required',
                'string',

                function ( $attribute, $value, $fail ) use ( $settings ) {
                    $validation = Helper::ValidateSettings( 'payment_method', $value, $settings );
                    if ( isset( $validation[ 'fail' ] ) ) $fail( $validation[ 'fail' ] );
                }
            ]
        ];
    }
}
