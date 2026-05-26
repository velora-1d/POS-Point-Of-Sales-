<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClockInAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'schedule_id' => ['nullable', 'exists:employee_schedules,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
