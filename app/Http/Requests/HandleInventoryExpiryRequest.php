<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HandleInventoryExpiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:acknowledge,deactivate,dispose,override'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
