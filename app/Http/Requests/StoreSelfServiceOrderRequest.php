<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSelfServiceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name'  => ['required', 'string', 'max:100'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'customer_email' => ['nullable', 'email', 'max:100'],
            'guests_count'   => ['required', 'integer', 'min:1', 'max:100'],
            'promo_code'     => ['nullable', 'string', 'max:50'],
            'notes'          => ['nullable', 'string'],
            'fcm_token'      => ['nullable', 'string'],
            'items'          => ['required', 'array', 'min:1'],
            'items.*.product_id'  => ['required', 'exists:products,id'],
            'items.*.variant_id'  => ['nullable', 'exists:product_variants,id'],
            'items.*.quantity'    => ['required', 'integer', 'min:1'],
            'items.*.unit_price'  => ['required', 'numeric', 'min:0'],
            'items.*.notes'       => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'guests_count.required' => 'Jumlah orang dalam grup wajib diisi.',
            'guests_count.integer'  => 'Jumlah orang harus berupa angka.',
            'guests_count.min'      => 'Minimal 1 orang.',
            'guests_count.max'      => 'Jumlah orang terlalu banyak.',
        ];
    }
}
