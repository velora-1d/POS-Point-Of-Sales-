<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductIngredientsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ingredients' => ['nullable', 'array'],
            'ingredients.*.raw_material_id' => ['nullable', 'string'],
            'ingredients.*.quantity' => ['nullable', 'numeric', 'gt:0'],
        ];
    }
}
