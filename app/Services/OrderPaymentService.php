<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Table;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class OrderPaymentService
{
    public function __construct(
        protected PakasirService $pakasirService,
    ) {
    }

    public function createKasirOrder(array $payload, User $actor): array
    {
        $outletId = $actor->outlet_id;

        if (!$outletId) {
            throw ValidationException::withMessages([
                'error' => 'User belum terhubung ke outlet aktif.',
            ]);
        }

        $customer = $this->resolveCustomer(
            $outletId,
            $payload['customer_id'] ?? null,
            $payload['customer_name'] ?? null,
            $payload['customer_phone'] ?? null,
            $payload['customer_email'] ?? null,
        );
        [$subtotal, $items] = $this->prepareItems($outletId, $payload['items']);

        return DB::transaction(function () use ($payload, $actor, $outletId, $customer, $subtotal, $items) {
            $order = $this->createOrderRecord($payload, $actor, $outletId, $customer?->id, $subtotal);

            foreach ($items as $item) {
                $order->items()->create($item);
            }

            $this->markTableOccupied($order);

            return $this->applyInitialPaymentFlow($order, $payload);
        });
    }

    public function processExistingOrderPayment(Order $order, array $payload, User $actor): array
    {
        $this->guardOutletAccess($order, $actor);
        $context = $this->resolveExistingPaymentContext($order);

        if (!$context) {
            throw ValidationException::withMessages([
                'error' => 'Order ini belum ada di tahap pembayaran.',
            ]);
        }

        return $payload['payment_method'] === 'cash'
            ? $this->settleExistingOrderWithCash($order, $payload, $context)
            : $this->settleExistingOrderWithQris($order, $context);
    }

    public function createSelfServiceOrder(string $tableToken, array $payload): array
    {
        $table = Table::query()
            ->where('qr_session_token', $tableToken)
            ->where('is_active', true)
            ->firstOrFail();

        $customer = $this->resolveCustomer(
            $table->outlet_id,
            null,
            $payload['customer_name'],
            $payload['customer_phone'],
            $payload['customer_email'] ?? null,
            'qr_meja',
        );
        [$subtotal, $items] = $this->prepareItems($table->outlet_id, $payload['items']);

        return DB::transaction(function () use ($payload, $table, $customer, $subtotal, $items, $tableToken) {
            $order = Order::create([
                'outlet_id' => $table->outlet_id,
                'table_id' => $table->id,
                'customer_id' => $customer?->id,
                'cashier_id' => null,
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'total_amount' => $subtotal,
                'paid_amount' => 0,
                'status' => 'payment_pending',
                'source' => 'qr_meja',
                'type' => 'dine_in',
                'notes' => $payload['notes'] ?? null,
                'estimated_time' => 15,
                'pending_started_at' => null,
                'pay_later' => false,
                'qr_session_token' => $tableToken,
                'metadata' => [
                    'payment' => [
                        'provider' => 'pakasir',
                        'method' => 'qris',
                        'status' => 'pending',
                        'context' => 'before_kitchen',
                    ],
                    'self_service' => [
                        'channel' => 'qr_meja',
                        'table_name' => $table->name,
                    ],
                ],
            ]);

            foreach ($items as $item) {
                $order->items()->create($item);
            }

            $checkout = $this->startQrisCheckout($order, 'before_kitchen');

            return [
                'order' => $order->fresh([
                    'table',
                    'customer.membership.tier',
                    'items.product',
                    'items.variant',
                ]),
                'message' => 'Checkout QRIS berhasil dibuat. Lanjutkan pembayaran untuk mengirim pesanan.',
                'paymentCheckout' => $checkout['paymentCheckout'],
            ];
        });
    }

    public function handlePakasirWebhook(array $payload): void
    {
        $order = Order::query()
            ->where('order_number', $payload['order_id'])
            ->first();

        if (!$order) {
            throw ValidationException::withMessages([
                'order_id' => 'Order webhook tidak ditemukan.',
            ]);
        }

        $expectedAmount = $this->resolveGatewayAmount($order);
        $transaction = $this->pakasirService
            ->getTransactionDetail($order->order_number, $expectedAmount)['transaction'] ?? null;

        if (!$transaction) {
            throw new RuntimeException('Detail transaksi dari Pakasir tidak ditemukan.');
        }

        if (($transaction['project'] ?? null) !== config('services.pakasir.slug')) {
            throw ValidationException::withMessages([
                'project' => 'Webhook project Pakasir tidak sesuai.',
            ]);
        }

        if ((int) ($transaction['amount'] ?? 0) !== $expectedAmount) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal webhook Pakasir tidak sesuai dengan order.',
            ]);
        }

        if (($transaction['status'] ?? null) !== 'completed') {
            return;
        }

        DB::transaction(function () use ($order, $transaction) {
            $freshOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            $paymentMeta = $this->getPaymentMeta($freshOrder);
            if (($paymentMeta['status'] ?? null) === 'paid') {
                return;
            }

            $context = $paymentMeta['context'] ?? 'after_service';
            $status = $context === 'before_kitchen' ? 'pending' : 'completed';

            $freshOrder->update([
                'status' => $status,
                'paid_amount' => $freshOrder->total_amount,
                'pay_later' => false,
                'pending_started_at' => $context === 'before_kitchen'
                    ? ($freshOrder->pending_started_at ?: now())
                    : $freshOrder->pending_started_at,
                'metadata' => $this->mergePaymentMeta($freshOrder, [
                    'provider' => 'pakasir',
                    'method' => $transaction['payment_method'] ?? 'qris',
                    'status' => 'paid',
                    'requested_at' => $paymentMeta['requested_at'] ?? now()->toIso8601String(),
                    'paid_at' => $transaction['completed_at'] ?? now()->toIso8601String(),
                    'checkout_url' => $paymentMeta['checkout_url'] ?? null,
                ]),
            ]);

            if ($context === 'before_kitchen' && $freshOrder->table_id) {
                $this->markTableOccupied($freshOrder);
            }

            if ($status === 'completed') {
                $this->syncTableStatus($freshOrder->table_id);
            }
        });
    }

    protected function applyInitialPaymentFlow(Order $order, array $payload): array
    {
        if ($payload['payment_option'] === 'pay_later') {
            return [
                'message' => 'Pesanan baru berhasil dikirim ke dapur.',
                'paymentCheckout' => null,
            ];
        }

        return $payload['payment_method'] === 'cash'
            ? $this->markOrderPaidBeforeKitchen($order, $payload['cash_received'] ?? null)
            : $this->startQrisCheckout($order, 'before_kitchen');
    }

    protected function markOrderPaidBeforeKitchen(Order $order, mixed $cashReceived): array
    {
        $changeAmount = $this->resolveCashChange($order, $cashReceived);

        $order->update([
            'paid_amount' => $order->total_amount,
            'pay_later' => false,
            'metadata' => $this->mergePaymentMeta($order, [
                'provider' => 'manual',
                'method' => 'cash',
                'status' => 'paid',
                'context' => 'before_kitchen',
                'requested_at' => now()->toIso8601String(),
                'paid_at' => now()->toIso8601String(),
                'cash_received' => $cashReceived !== null ? (float) $cashReceived : (float) $order->total_amount,
                'change_amount' => $changeAmount,
                'checkout_url' => null,
            ]),
        ]);

        return [
            'message' => $changeAmount > 0
                ? 'Pembayaran tunai diterima. Order langsung masuk ke dapur.'
                : 'Order lunas dan langsung masuk ke dapur.',
            'paymentCheckout' => null,
        ];
    }

    protected function startQrisCheckout(Order $order, string $context): array
    {
        $checkoutUrl = $this->pakasirService->buildQrisCheckoutUrl(
            $order->order_number,
            $this->resolveGatewayAmount($order),
            route('kasir.order'),
        );

        $order->update([
            'status' => $context === 'before_kitchen' ? 'payment_pending' : $order->status,
            'pay_later' => false,
            'metadata' => $this->mergePaymentMeta($order, [
                'provider' => 'pakasir',
                'method' => 'qris',
                'status' => 'pending',
                'context' => $context,
                'requested_at' => now()->toIso8601String(),
                'paid_at' => null,
                'checkout_url' => $checkoutUrl,
            ]),
        ]);

        return [
            'message' => $context === 'before_kitchen'
                ? 'Checkout QRIS dibuat. Order akan masuk ke dapur setelah pembayaran terkonfirmasi.'
                : 'Checkout QRIS dibuat. Order akan selesai otomatis setelah pembayaran terkonfirmasi.',
            'paymentCheckout' => [
                'provider' => 'pakasir',
                'method' => 'qris',
                'order_number' => $order->order_number,
                'amount' => $this->resolveGatewayAmount($order),
                'context' => $context,
                'payment_url' => $checkoutUrl,
            ],
        ];
    }

    protected function settleExistingOrderWithCash(Order $order, array $payload, string $context): array
    {
        if ($order->status === 'payment_pending') {
            $this->cancelPendingGatewayIfNeeded($order);
        }

        $changeAmount = $this->resolveCashChange($order, $payload['cash_received'] ?? null);
        $status = $context === 'before_kitchen' ? 'pending' : 'completed';

        $order->update([
            'status' => $status,
            'paid_amount' => $order->total_amount,
            'pay_later' => false,
            'pending_started_at' => $context === 'before_kitchen'
                ? ($order->pending_started_at ?: now())
                : $order->pending_started_at,
            'metadata' => $this->mergePaymentMeta($order, [
                'provider' => 'manual',
                'method' => 'cash',
                'status' => 'paid',
                'context' => $context,
                'requested_at' => now()->toIso8601String(),
                'paid_at' => now()->toIso8601String(),
                'cash_received' => $payload['cash_received'] !== null
                    ? (float) $payload['cash_received']
                    : (float) $order->total_amount,
                'change_amount' => $changeAmount,
                'checkout_url' => null,
            ]),
        ]);

        if ($status === 'completed') {
            $this->syncTableStatus($order->table_id);
        }

        return [
            'message' => $status === 'completed'
                ? 'Pembayaran tunai diterima dan order selesai.'
                : 'Pembayaran tunai diterima. Order masuk ke dapur.',
            'paymentCheckout' => null,
        ];
    }

    protected function settleExistingOrderWithQris(Order $order, string $context): array
    {
        return $this->startQrisCheckout($order, $context);
    }

    protected function cancelPendingGatewayIfNeeded(Order $order): void
    {
        $paymentMeta = $this->getPaymentMeta($order);

        if (($paymentMeta['method'] ?? null) !== 'qris' || ($paymentMeta['status'] ?? null) !== 'pending') {
            return;
        }

        $transaction = $this->pakasirService
            ->getTransactionDetail($order->order_number, $this->resolveGatewayAmount($order))['transaction'] ?? null;

        if (($transaction['status'] ?? null) === 'completed') {
            throw ValidationException::withMessages([
                'error' => 'Transaksi QRIS sudah dibayar. Refresh data order terlebih dahulu.',
            ]);
        }

        $this->pakasirService->cancelTransaction($order->order_number, $this->resolveGatewayAmount($order));
    }

    protected function createOrderRecord(
        array $payload,
        User $actor,
        string $outletId,
        ?string $customerId,
        float $subtotal,
    ): Order {
        $discount = 0;
        $totalAmount = $subtotal - $discount;

        return Order::create([
            'outlet_id' => $outletId,
            'table_id' => $payload['order_type'] === 'takeaway' ? null : $payload['table_id'],
            'customer_id' => $customerId,
            'cashier_id' => $actor->id,
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'total_amount' => $totalAmount,
            'paid_amount' => 0,
            'status' => 'pending',
            'source' => 'kasir',
            'type' => $payload['order_type'],
            'notes' => $payload['notes'] ?? null,
            'estimated_time' => 15,
            'pending_started_at' => now(),
            'pay_later' => ($payload['payment_option'] ?? 'pay_later') === 'pay_later',
            'metadata' => $this->mergeArray([], [
                'payment' => [
                    'provider' => null,
                    'method' => $payload['payment_option'] === 'pay_now' ? ($payload['payment_method'] ?? null) : null,
                    'status' => $payload['payment_option'] === 'pay_now' ? 'pending' : 'unpaid',
                    'context' => $payload['payment_option'] === 'pay_now' ? 'before_kitchen' : 'after_service',
                ],
            ]),
        ]);
    }

    protected function resolveCustomer(
        string $outletId,
        ?string $customerId,
        ?string $customerName,
        ?string $customerPhone,
        ?string $customerEmail,
        string $registeredVia = 'kasir',
    ): ?Customer {
        if ($customerId) {
            return Customer::query()
                ->where('outlet_id', $outletId)
                ->find($customerId);
        }

        if (!$customerPhone) {
            return null;
        }

        $customer = Customer::firstOrNew([
            'outlet_id' => $outletId,
            'phone' => $customerPhone,
        ]);

        $customer->name = $customerName ?: ($customer->name ?: 'Pelanggan POS');
        $customer->email = $customerEmail ?: $customer->email;
        $customer->is_active = true;
        $customer->registered_via = $customer->exists ? $customer->registered_via : $registeredVia;
        $customer->save();

        return $customer;
    }

    protected function prepareItems(string $outletId, array $payloadItems): array
    {
        $productIds = collect($payloadItems)->pluck('product_id')->unique()->values();
        $products = Product::query()
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->where('is_available', true)
            ->whereIn('id', $productIds)
            ->with(['prices' => function ($query) use ($outletId) {
                $query->where('outlet_id', $outletId)
                    ->where('is_active', true)
                    ->orderByDesc('created_at');
            }])
            ->get()
            ->keyBy('id');

        $variantIds = collect($payloadItems)
            ->pluck('variant_id')
            ->filter()
            ->unique()
            ->values();
        $variants = ProductVariant::query()
            ->whereIn('id', $variantIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $items = [];
        $subtotal = 0;

        foreach ($payloadItems as $item) {
            $product = $products->get($item['product_id']);
            if (!$product) {
                throw ValidationException::withMessages([
                    'error' => 'Ada item yang tidak valid atau sudah tidak tersedia.',
                ]);
            }

            $variantId = $item['variant_id'] ?? null;
            $variant = null;
            if ($variantId) {
                $variant = $variants->get($variantId);
                if (!$variant || $variant->product_id !== $product->id) {
                    throw ValidationException::withMessages([
                        'error' => 'Varian produk tidak valid untuk item yang dipilih.',
                    ]);
                }
            }

            $basePrice = (float) ($product->prices->first()?->price ?? $product->base_price ?? 0);
            $variantPrice = (float) ($variant?->additional_price ?? 0);
            $unitPrice = $basePrice + $variantPrice;
            $itemTotal = (int) $item['quantity'] * $unitPrice;
            $subtotal += $itemTotal;

            $items[] = [
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'quantity' => (int) $item['quantity'],
                'unit_price' => $unitPrice,
                'total_price' => $itemTotal,
                'notes' => $item['notes'] ?? null,
            ];
        }

        return [$subtotal, $items];
    }

    protected function resolveExistingPaymentContext(Order $order): ?string
    {
        $paymentMeta = $this->getPaymentMeta($order);

        if (($paymentMeta['status'] ?? null) === 'paid' || (float) $order->paid_amount >= (float) $order->total_amount) {
            throw ValidationException::withMessages([
                'error' => 'Order ini sudah lunas.',
            ]);
        }

        if ($order->status === 'payment_pending') {
            return 'before_kitchen';
        }

        if (in_array($order->status, ['waiting_bar_approval', 'ready', 'delivered'], true)) {
            return 'after_service';
        }

        return null;
    }

    protected function resolveCashChange(Order $order, mixed $cashReceived): float
    {
        $received = $cashReceived !== null ? (float) $cashReceived : (float) $order->total_amount;
        $total = (float) $order->total_amount;

        if ($received < $total) {
            throw ValidationException::withMessages([
                'cash_received' => 'Nominal tunai yang diterima kurang dari total tagihan.',
            ]);
        }

        return $received - $total;
    }

    protected function mergePaymentMeta(Order $order, array $paymentMeta): array
    {
        $metadata = $order->metadata ?? [];
        $existingPayment = $metadata['payment'] ?? [];

        return array_merge($metadata, [
            'payment' => array_merge($existingPayment, array_filter($paymentMeta, fn ($value) => $value !== '__KEEP__')),
        ]);
    }

    protected function getPaymentMeta(Order $order): array
    {
        return $order->metadata['payment'] ?? [];
    }

    protected function resolveGatewayAmount(Order $order): int
    {
        return (int) round((float) $order->total_amount);
    }

    protected function markTableOccupied(Order $order): void
    {
        if (!$order->table_id) {
            return;
        }

        Table::query()
            ->whereKey($order->table_id)
            ->update(['status' => 'occupied']);
    }

    protected function syncTableStatus(?string $tableId): void
    {
        if (!$tableId) {
            return;
        }

        $hasActiveOrder = Order::query()
            ->where('table_id', $tableId)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->exists();

        Table::query()
            ->whereKey($tableId)
            ->update(['status' => $hasActiveOrder ? 'occupied' : 'available']);
    }

    protected function guardOutletAccess(Order $order, User $actor): void
    {
        if ($order->outlet_id !== $actor->outlet_id) {
            throw ValidationException::withMessages([
                'error' => 'Order ini tidak berada di outlet aktif Anda.',
            ]);
        }
    }

    protected function mergeArray(array $base, array $override): array
    {
        return array_merge($base, $override);
    }
}
