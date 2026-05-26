<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRawMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'unit' => ['required', 'string', 'max:30'],
            'quantity' => ['required', 'integer', 'min:0'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'cost_per_unit' => ['required', 'numeric', 'min:0'],
            'track_expired' => ['nullable', 'boolean'],
            'expired_action' => ['nullable', 'string', 'max:50'],
            'expired_reminder_days' => ['nullable', 'array'],
            'expired_reminder_days.*' => ['integer', 'min:0', 'max:365'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
