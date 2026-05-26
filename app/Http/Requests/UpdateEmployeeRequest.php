<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        /** @var User $employee */
        $employee = $this->route('employee');

        return [
            'outlet_id' => ['required', 'exists:outlets,id'],
            'role_id' => ['required', 'exists:roles,id'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($employee?->id)],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($employee?->id)],
            'password' => ['nullable', 'string', 'min:8', 'max:100'],
            'approval_pin' => ['nullable', 'digits:6'],
            'join_date' => ['required', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
