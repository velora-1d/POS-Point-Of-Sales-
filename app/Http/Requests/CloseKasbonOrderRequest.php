<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloseKasbonOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
