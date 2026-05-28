<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CashierReportIndexRequest extends FormRequest
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
            'user_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
