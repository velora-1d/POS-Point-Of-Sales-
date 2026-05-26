<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'clock_in' => ['required', 'date'],
            'clock_out' => ['nullable', 'date', 'after_or_equal:clock_in'],
            'notes' => ['nullable', 'string', 'max:500'],
            'correction_reason' => ['required', 'string', 'max:500'],
        ];
    }
}
