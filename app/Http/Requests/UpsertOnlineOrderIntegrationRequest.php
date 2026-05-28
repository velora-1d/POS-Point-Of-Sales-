<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertOnlineOrderIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
            'merchant_id' => ['nullable', 'string', 'max:100'],
            'environment' => ['required', Rule::in(['sandbox', 'production'])],
            'api_key' => ['nullable', 'string', 'max:255'],
            'api_secret' => ['nullable', 'string', 'max:255'],
            'outlet_mappings' => ['required', 'array', 'min:1'],
            'outlet_mappings.*.outlet_id' => ['required', 'exists:outlets,id'],
            'outlet_mappings.*.external_outlet_id' => ['nullable', 'string', 'max:100'],
        ];
    }
}
