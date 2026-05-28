<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpsertApprovalRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'outlet_id' => ['required', 'exists:outlets,id'],
            'manual_discount_enabled' => ['required', 'boolean'],
            'manual_discount_threshold' => ['required', 'numeric', 'min:0'],
            'order_edit_enabled' => ['required', 'boolean'],
            'order_edit_threshold' => ['required', 'numeric', 'min:0'],
        ];
    }
}
