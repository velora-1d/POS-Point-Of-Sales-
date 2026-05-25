<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SplitOrderBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'approval_pin' => ['nullable', 'string', 'max:20'],
            'item_splits' => ['required', 'array', 'min:1'],
            'item_splits.*.order_item_id' => ['required', 'exists:order_items,id'],
            'item_splits.*.quantity' => ['required', 'integer', 'min:0'],
        ];
    }
}
