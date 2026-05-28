<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertBackupSecuritySettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'outlet_id' => ['required', 'exists:outlets,id'],
            'auto_backup_enabled' => ['required', 'boolean'],
            'auto_backup_frequency' => ['required', Rule::in(['daily', 'weekly', 'monthly'])],
            'auto_backup_time' => ['required', 'date_format:H:i'],
            'retention_days' => ['required', 'integer', 'min:7', 'max:365'],
            'backup_channel' => ['required', Rule::in(['local_download', 'cloud_storage', 'hybrid'])],
            'encryption_enabled' => ['required', 'boolean'],
            'two_factor_required' => ['required', 'boolean'],
        ];
    }
}
