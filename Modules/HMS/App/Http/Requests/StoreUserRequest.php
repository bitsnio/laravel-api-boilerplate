<?php

namespace Modules\HMS\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends StoreValidationErrorRequest
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
            'user_type' => 'string',
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:5',
            'main_module_id' => 'required|string',
            'role' => 'required',
            'is_deleted' => 'boolean',
            'created_by' => 'integer',
            'updated_by' => 'integer',
            'deleted_by' => 'integer'
        ];
    }
}
