<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'uuid', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'hpp' => ['nullable', 'numeric', 'min:0'],
            'is_available' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'track_stock' => ['required', 'boolean'],
            'track_expired' => ['required', 'boolean'],
            'expired_action' => ['nullable', 'string', 'in:notify_only,auto_deactivate'],
            'expired_reminder_days' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer', 'min:0'],

            // Variants
            'variants' => ['nullable', 'array'],
            'variants.*.name' => ['required_with:variants', 'string', 'max:100'],
            'variants.*.additional_price' => ['required_with:variants', 'numeric', 'min:0'],
            'variants.*.is_active' => ['required_with:variants', 'boolean'],

            // Multi Prices
            'prices' => ['nullable', 'array'],
            'prices.*.tier' => ['required_with:prices', 'string', 'in:normal,member,grosir,custom'],
            'prices.*.tier_label' => ['nullable', 'string', 'max:50'],
            'prices.*.price' => ['required_with:prices', 'numeric', 'min:0'],
            'prices.*.happy_hour_start' => ['nullable', 'date_format:H:i'],
            'prices.*.happy_hour_end' => ['nullable', 'date_format:H:i', 'after:prices.*.happy_hour_start'],
            'prices.*.is_active' => ['required_with:prices', 'boolean'],

            // Ingredients (Recipe)
            'ingredients' => ['nullable', 'array'],
            'ingredients.*.raw_material_id' => ['required_with:ingredients', 'uuid', 'exists:raw_materials,id'],
            'ingredients.*.quantity' => ['required_with:ingredients', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.uploaded' => 'Gagal mengupload gambar. Pastikan ukuran file tidak melebihi 2MB dan formatnya sesuai.',
        ];
    }
}
