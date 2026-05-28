<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertPaymentGatewayConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'outlet_id' => ['required', 'exists:outlets,id'],
            'provider' => ['required', Rule::in(['pakasir'])],
            'is_active' => ['nullable', 'boolean'],
            'base_url' => ['nullable', 'url', 'max:255'],
            'project_slug' => ['nullable', 'string', 'max:100'],
            'callback_url' => ['nullable', 'url', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:500'],
            'api_secret' => ['nullable', 'string', 'max:500'],
            'active_payment_methods' => ['nullable', 'array'],
            'active_payment_methods.*' => ['string', Rule::in(['qris', 'ewallet', 'debit', 'transfer'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $methods = $this->input('active_payment_methods', []);

        $this->merge([
            'is_active' => $this->boolean('is_active', false),
            'active_payment_methods' => is_array($methods) ? $methods : [],
        ]);
    }
}
