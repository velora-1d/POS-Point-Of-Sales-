<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'current_stock' => ['required', 'integer', 'min:0'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'unit' => ['nullable', 'string', 'max:20'],
            'batch_code' => ['nullable', 'string', 'max:80'],
            'expired_date' => ['nullable', 'date'],
        ];
    }
}
