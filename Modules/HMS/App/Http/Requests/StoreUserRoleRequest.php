<?php

namespace Modules\HMS\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRoleRequest extends StoreValidationErrorRequest
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
            'role_name' => 'required|string',
            'property_id' => 'nullable|sometimes|string',
            'sub_module_id' => 'required|nullable|string',
            'permissions' => 'sometimes|nullable|string',
        ];
    }
}
