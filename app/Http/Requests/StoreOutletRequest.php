<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOutletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:20'],
            'workflow_statuses' => ['nullable', 'string', 'max:1000'],
            'default_receipt_method' => ['required', Rule::in(['print', 'whatsapp', 'skip'])],
            'bar_approval_enabled' => ['nullable', 'boolean'],
            'customer_can_view_status' => ['nullable', 'boolean'],
            'customer_can_edit_order' => ['nullable', 'boolean'],
        ];
    }
}
