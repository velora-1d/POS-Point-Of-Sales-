<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'order_type' => ['required', Rule::in(['dine_in', 'takeaway'])],
            'table_id' => [
                'nullable',
                'exists:tables,id',
                Rule::requiredIf(fn () => $this->input('order_type') === 'dine_in'),
            ],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['nullable', 'string', 'max:100'],
            'customer_phone' => ['nullable', 'string', 'max:20'],
            'customer_email' => ['nullable', 'email', 'max:100'],
            'promo_code' => ['nullable', 'string', 'max:50'],
            'approval_pin' => ['nullable', 'string', 'max:20'],
            'reservation_id' => ['nullable', 'exists:table_reservations,id'],
            'payment_option' => ['required', Rule::in(['pay_later', 'pay_now'])],
            'payment_method' => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('payment_option') === 'pay_now'),
                Rule::in(['cash', 'qris']),
            ],
            'cash_received' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
