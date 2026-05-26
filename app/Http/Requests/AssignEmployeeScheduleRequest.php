<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignEmployeeScheduleRequest extends FormRequest
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
            'shift_template_id' => ['required', 'exists:shift_templates,id'],
            'schedule_date' => ['required', 'date'],
        ];
    }
}
