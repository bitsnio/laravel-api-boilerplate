<?php

namespace Modules\HMS\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyRequest extends StoreCompanyRequest
{
    public function rules(): array {
        return [
            'company_name' => 'string|max:100',
            'company_email' => 'email',
            'user_name' => 'string',
            'password' => 'min:5',
            'main_module_id' => 'string',
            'company_phone' => 'string|max:20',
            'country' => 'string|max:100',
            'city' => 'string|max:100',
            'street_address' => 'string|max:200'
        ];
    }
}
