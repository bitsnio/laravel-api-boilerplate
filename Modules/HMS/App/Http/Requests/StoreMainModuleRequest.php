<?php

namespace Modules\HMS\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMainModuleRequest extends StoreValidationErrorRequest
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
            'menu_order' => 'integer|max:100',
            'slug' => 'required|string|max:100',
            'title' => 'required|string|max:100',
            'route' => 'required|string|max:100',
            'icon' => 'required|string|max:100',
            'is_deleted' => 'boolean',
            'created_by' => 'integer',
            'updated_by' => 'integer',
            'deleted_by' => 'integer'
        ];
    }
}
