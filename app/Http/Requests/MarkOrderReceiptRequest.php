<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarkOrderReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'receipt_method' => ['required', Rule::in(['print', 'whatsapp', 'skip'])],
            'receipt_phone' => ['nullable', 'string', 'max:20'],
        ];
    }
}
