<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MergeOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'approval_pin' => ['nullable', 'string', 'max:20'],
            'order_ids' => ['required', 'array', 'min:2'],
            'order_ids.*' => ['required', 'exists:orders,id'],
        ];
    }
}
