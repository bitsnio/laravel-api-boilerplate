<?php

namespace Modules\HMS\App\Http\Requests;

use Modules\HMS\App\Utilities\Helper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class StoreCompanyRequest extends StoreValidationErrorRequest
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
            'company_name' => 'required|string|max:100',
            'company_email' => 'required|email',
            'user_name' => 'required|string',
            'password' => 'required|min:5',
            'main_module_id' => 'required|string',
            'company_phone' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'street_address' => 'required|string|max:200',
            'is_deleted' => 'boolean',
            'created_by' => 'integer',
            'updated_by' => 'integer',
            'deleted_by' => 'integer'
        ];
    }
    // protected function failedValidation(Validator $validator){
    //     if($validator->failed()){
    //        return Helper::errorResponse( $validator->errors(), 400);
    //     }
    // }
}
