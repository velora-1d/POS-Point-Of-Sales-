<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalesReportIndexRequest extends FormRequest
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
            'source' => ['nullable', Rule::in(['kasir', 'qr_meja', 'gofood', 'grabfood', 'shopeefood', 'maximfood'])],
            'payment_method' => ['nullable', Rule::in(['cash', 'qris', 'debit', 'ewallet', 'kasbon'])],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:25'],
        ];
    }
}
