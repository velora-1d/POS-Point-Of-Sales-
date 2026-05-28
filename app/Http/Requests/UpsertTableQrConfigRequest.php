<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertTableQrConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'outlet_id' => ['required', 'exists:outlets,id'],
            'store_slug' => ['required', 'string', 'min:3', 'max:60', 'regex:/^[a-z0-9-]+$/'],
            'qr_template' => ['required', Rule::in(['classic_sharp', 'modern_rounded', 'branded_center'])],
            'primary_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $slug = strtolower(trim((string) $this->input('store_slug', '')));
        $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug ?? '') ?? '';
        $slug = trim($slug, '-');

        $this->merge([
            'store_slug' => $slug,
            'primary_color' => strtoupper(trim((string) $this->input('primary_color', '#111827'))),
        ]);
    }
}
