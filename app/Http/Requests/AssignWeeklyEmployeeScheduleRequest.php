<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignWeeklyEmployeeScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'outlet_id' => ['required', 'exists:outlets,id'],
            'user_id' => ['required', 'exists:users,id'],
            'week_start' => ['required', 'date'],
            'days' => ['required', 'array', 'size:7'],
            'days.*' => ['nullable', 'exists:shift_templates,id'],
        ];
    }
}
