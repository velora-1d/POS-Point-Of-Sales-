<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessOrderPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::in(['cash', 'qris', 'ewallet', 'debit', 'transfer'])],
            'promo_code' => ['nullable', 'string', 'max:50'],
            'approval_pin' => ['nullable', 'string', 'max:20'],
            'cash_received' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
