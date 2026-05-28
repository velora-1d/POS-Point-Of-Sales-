<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryReportIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'outlet_id' => ['nullable', 'exists:outlets,id'],
            'type' => ['nullable', Rule::in(['all', 'product', 'raw_material'])],
            'status' => ['nullable', Rule::in(['all', 'healthy', 'low', 'out', 'inactive'])],
        ];
    }
}
