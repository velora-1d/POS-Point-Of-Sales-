<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertNotificationSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'outlet_id' => ['required', 'exists:outlets,id'],
            'low_stock_enabled' => ['nullable', 'boolean'],
            'low_stock_channels' => ['nullable', 'array'],
            'low_stock_channels.*' => ['string', Rule::in(['in_app', 'whatsapp', 'email'])],
            'kasbon_due_enabled' => ['nullable', 'boolean'],
            'kasbon_due_channels' => ['nullable', 'array'],
            'kasbon_due_channels.*' => ['string', Rule::in(['in_app', 'whatsapp', 'email'])],
            'kasbon_due_threshold_days' => ['required', 'integer', 'min:1', 'max:30'],
            'online_order_enabled' => ['nullable', 'boolean'],
            'online_order_channels' => ['nullable', 'array'],
            'online_order_channels.*' => ['string', Rule::in(['in_app', 'whatsapp', 'email'])],
            'table_duration_alert_enabled' => ['nullable', 'boolean'],
            'table_duration_warning_minutes' => ['required', 'integer', 'min:1'],
            'table_duration_danger_minutes' => ['required', 'integer', 'min:1', 'gt:table_duration_warning_minutes'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $channels = fn (string $field) => is_array($this->input($field)) ? $this->input($field) : [];

        $this->merge([
            'low_stock_enabled' => $this->boolean('low_stock_enabled', false),
            'low_stock_channels' => $channels('low_stock_channels'),
            'kasbon_due_enabled' => $this->boolean('kasbon_due_enabled', false),
            'kasbon_due_channels' => $channels('kasbon_due_channels'),
            'kasbon_due_threshold_days' => (int) $this->input('kasbon_due_threshold_days', 3),
            'online_order_enabled' => $this->boolean('online_order_enabled', false),
            'online_order_channels' => $channels('online_order_channels'),
            'table_duration_alert_enabled' => $this->boolean('table_duration_alert_enabled', false),
            'table_duration_warning_minutes' => (int) $this->input('table_duration_warning_minutes', 90),
            'table_duration_danger_minutes' => (int) $this->input('table_duration_danger_minutes', 180),
        ]);
    }
}
