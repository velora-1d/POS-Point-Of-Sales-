<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RbacIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'outlet_id' => ['nullable', 'exists:outlets,id'],
        ];
    }
}
