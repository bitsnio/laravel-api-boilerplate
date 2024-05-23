<?php

namespace Modules\HMS\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRoleRequest extends StoreUserRoleRequest{
    
    public function rules(): array{
        return [
            'role_name' =>'string',
            'sub_module_id' => 'string'
        ];
    
    }
}
