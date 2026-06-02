<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMembershipTierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'tier' => ['nullable', 'string', 'max:50'],
            'point_threshold' => ['required', 'integer', 'min:0'],
            'point_rate_per_amount' => ['required', 'numeric', 'min:0', 'max:1'],
            'discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
