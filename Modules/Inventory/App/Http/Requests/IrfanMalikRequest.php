<?php

namespace Modules\Inventory\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IrfanMalikRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            "name" => "required|string",
            "age" => "integer|required",
            "about" => "nullable|string"
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
