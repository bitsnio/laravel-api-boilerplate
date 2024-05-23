<?php

namespace Modules\HMS\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends StoreUserRequest{
    
    
   
    public function rules(): array{
        return [
            'name' =>'string',
            'email' => 'required|email',
            'password' => 'min:5',
            'role' => 'required'
        ];
    
    }
}
