<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportExportDownloadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'report_type' => ['required', Rule::in([
                'sales',
                'outlets',
                'cashiers',
                'top_products',
                'inventory',
                'attendance_shifts',
                'expenses',
            ])],
            'format' => ['required', Rule::in(['pdf', 'excel'])],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'outlet_id' => ['nullable', 'exists:outlets,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'category' => ['nullable', 'string', 'max:50'],
            'source' => ['nullable', Rule::in(['kasir', 'qr_meja', 'gofood', 'grabfood'])],
            'payment_method' => ['nullable', Rule::in(['cash', 'qris', 'debit', 'ewallet', 'kasbon'])],
            'type' => ['nullable', Rule::in(['all', 'product', 'raw_material'])],
            'status' => ['nullable', Rule::in(['all', 'healthy', 'low', 'out', 'inactive'])],
            'search' => ['nullable', 'string', 'max:160'],
        ];
    }
}
