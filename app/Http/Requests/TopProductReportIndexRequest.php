<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TopProductReportIndexRequest extends FormRequest
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
            'category_id' => ['nullable', 'exists:categories,id'],
        ];
    }
}
