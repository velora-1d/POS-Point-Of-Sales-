<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePromoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->baseRules();
    }

    protected function prepareForValidation(): void
    {
        $this->normalizePayload();
    }

    protected function baseRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:50'],
            'type' => ['required', Rule::in(['percent', 'nominal', 'buy_x_get_y'])],
            'apply_method' => ['required', Rule::in(['auto', 'manual', 'both'])],
            'discount_percent' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'min_transaction_amount' => ['nullable', 'numeric', 'min:0'],
            'buy_quantity' => ['nullable', 'integer', 'min:0'],
            'get_quantity' => ['nullable', 'integer', 'min:0'],
            'can_stack' => ['nullable', 'boolean'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'happy_hour_start' => ['nullable', 'date_format:H:i'],
            'happy_hour_end' => ['nullable', 'date_format:H:i'],
            'status' => ['required', Rule::in(['active', 'inactive', 'expired', 'limit_reached'])],
            'rules' => ['nullable', 'array'],
            'rules.*.trigger' => ['required', Rule::in(['product', 'category', 'transaction', 'time', 'payment_method', 'member_tier'])],
            'rules.*.reference_id' => ['nullable', 'string', 'max:100'],
            'rules.*.reference_value' => ['nullable', 'string', 'max:50'],
        ];
    }

    protected function normalizePayload(): void
    {
        $rules = collect($this->input('rules', []))
            ->map(function ($rule) {
                return [
                    'trigger' => $rule['trigger'] ?? null,
                    'reference_id' => $rule['reference_id'] ?: null,
                    'reference_value' => $rule['reference_value'] ?: null,
                ];
            })
            ->values()
            ->all();

        $this->merge([
            'can_stack' => $this->boolean('can_stack'),
            'rules' => $rules,
        ]);
    }
}
