<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateKitchenOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:start_cooking,finish_cooking,set_estimate'],
            'estimate_minutes' => ['nullable', 'integer', 'min:5', 'max:90'],
        ];
    }
}
