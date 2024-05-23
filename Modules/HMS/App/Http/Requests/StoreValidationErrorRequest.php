<?php

namespace Modules\HMS\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Modules\HMS\App\Utilities\Helper;
use Illuminate\Contracts\Validation\Validator;

class StoreValidationErrorRequest extends FormRequest
{
    protected function failedValidation(Validator $validator){
        if($validator->failed()){
            return Helper::errorResponse( $validator->errors(), 400);
        }
    }
}
