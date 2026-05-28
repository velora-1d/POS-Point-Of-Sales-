<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertPrinterConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'outlet_id' => ['required', 'exists:outlets,id'],
            'printer_type' => ['required', Rule::in(['thermal', 'dot_matrix'])],
            'connection_type' => ['required', Rule::in(['usb', 'network'])],
            'device_name' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'ip', 'max:100'],
            'port' => ['nullable', 'integer', 'between:1,65535'],
            'default_receipt_method' => ['required', Rule::in(['print', 'whatsapp', 'skip'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $connectionType = (string) $this->input('connection_type', 'usb');

        $this->merge([
            'device_name' => trim((string) $this->input('device_name', '')) ?: null,
            'ip_address' => trim((string) $this->input('ip_address', '')) ?: null,
            'port' => $this->filled('port') ? (int) $this->input('port') : null,
        ]);

        if ($connectionType === 'usb') {
            $this->merge([
                'ip_address' => null,
                'port' => null,
            ]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('connection_type') === 'usb' && !$this->filled('device_name')) {
                $validator->errors()->add('device_name', 'Nama device wajib diisi untuk koneksi USB.');
            }

            if ($this->input('connection_type') === 'network') {
                if (!$this->filled('ip_address')) {
                    $validator->errors()->add('ip_address', 'IP address wajib diisi untuk printer network.');
                }

                if (!$this->filled('port')) {
                    $validator->errors()->add('port', 'Port wajib diisi untuk printer network.');
                }
            }
        });
    }
}
