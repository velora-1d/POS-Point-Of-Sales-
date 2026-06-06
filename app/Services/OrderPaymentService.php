<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Table;
use App\Models\TableReservation;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class OrderPaymentService
{
    public function __construct(
        protected PakasirService $pakasirService,
        protected PaymentGatewayConfigService $paymentGatewayConfigService,
        protected TableReservationService $tableReservationService,
        protected PromoEngineService $promoEngineService,
        protected ShiftService $shiftService,
        protected ApprovalRuleService $approvalRuleService,
        protected TableManagementService $tableManagementService,
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

        $activeShift = $this->shiftService->requireActiveShiftForOutlet($outletId);

        $customer = $this->resolveCustomer(
            $outletId,
            $payload['customer_id'] ?? null,
            $payload['customer_name'] ?? null,
            $payload['customer_phone'] ?? null,
            $payload['customer_email'] ?? null,
        );
        $reservation = $this->resolveReservation(
            $outletId,
            $payload['reservation_id'] ?? null,
            $payload['order_type'],
            $payload['table_id'] ?? null,
        );
        [, $items] = $this->prepareItems($outletId, $payload['items']);
        $digitalMethods = ['qris', 'ewallet', 'debit', 'transfer'];
        if (($payload['payment_option'] ?? 'pay_later') === 'pay_now' && in_array($payload['payment_method'] ?? null, $digitalMethods, true)) {
            $this->paymentGatewayConfigService->assertMethodEnabledForOutlet($outletId, $payload['payment_method']);
        }
        $pricing = $this->promoEngineService->calculate(
            $outletId,
            $items,
            $customer?->loadMissing('membership.tier'),
            ($payload['payment_option'] ?? 'pay_later') === 'pay_now'
                ? ($payload['payment_method'] ?? null)
                : null,
            $payload['promo_code'] ?? null,
        );
        $this->approvalRuleService->assertManualDiscountApproval(
            $outletId,
            $pricing,
            $payload['approval_pin'] ?? null,
        );

        return DB::transaction(function () use ($payload, $actor, $outletId, $customer, $reservation, $items, $pricing, $activeShift) {
            $order = $this->createOrderRecord($payload, $actor, $outletId, $customer?->id, $pricing, $activeShift->id);

            $this->persistOrderItems($order, $items);
            $this->promoEngineService->syncUsageDifference(
                [],
                $this->promoEngineService->extractPromoIdsFromMetadata($order->metadata ?? []),
            );

            $this->markTableOccupied($order);
            $this->tableReservationService->completeReservationForOrder(
                $reservation?->id,
                $order->outlet_id,
                $order->table_id,
            );

            return $this->applyInitialPaymentFlow($order, $payload);
        });
    }

    public function processExistingOrderPayment(Order $order, array $payload, User $actor): array
    {
        $this->guardOutletAccess($order, $actor);
        $activeShift = $this->shiftService->requireActiveShiftForOutlet($order->outlet_id);
        $context = $this->resolveExistingPaymentContext($order);

        if (!$context) {
            throw ValidationException::withMessages([
                'error' => 'Order ini belum ada di tahap pembayaran.',
            ]);
        }

        if (!$order->shift_id || $order->shift_id !== $activeShift->id) {
            $this->shiftService->assignOrderToActiveShiftIfMissing($order->id, $order->outlet_id);
            $order->refresh();
        }

        $pricing = $this->calculateExistingOrderPricing(
            $order,
            $payload['payment_method'],
            $payload['promo_code'] ?? data_get($order->metadata, 'promo.manual_code'),
        );
        $this->approvalRuleService->assertManualDiscountApproval(
            $order->outlet_id,
            $pricing,
            $payload['approval_pin'] ?? null,
        );
        $this->applyExistingOrderPricing($order, $pricing);

        $digitalMethods = ['qris', 'ewallet', 'debit', 'transfer'];
        if (in_array($payload['payment_method'] ?? null, $digitalMethods, true)) {
            $this->paymentGatewayConfigService->assertMethodEnabledForOutlet($order->outlet_id, $payload['payment_method']);
        }

        return $payload['payment_method'] === 'cash'
            ? $this->settleExistingOrderWithCash($order, $payload, $context)
            : $this->settleExistingOrderWithGateway($order, $context, $payload['payment_method']);
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
        [, $items] = $this->prepareItems($table->outlet_id, $payload['items']);
        $this->paymentGatewayConfigService->assertMethodEnabledForOutlet($table->outlet_id, 'qris');
        $pricing = $this->promoEngineService->calculate(
            $table->outlet_id,
            $items,
            $customer?->loadMissing('membership.tier'),
            'qris',
            $payload['promo_code'] ?? null,
        );

        return DB::transaction(function () use ($payload, $table, $customer, $items, $tableToken, $pricing) {
            $order = Order::create([
                'outlet_id'      => $table->outlet_id,
                'table_id'       => $table->id,
                'customer_id'    => $customer?->id,
                'cashier_id'     => null,
                'subtotal'       => $pricing['subtotal'],
                'discount_amount'=> $pricing['discount_amount'],
                'total_amount'   => $pricing['total_amount'],
                'paid_amount'    => 0,
                'status'         => 'pending',
                'source'         => 'qr_meja',
                'type'           => 'dine_in',
                'guests_count'   => max(1, (int) ($payload['guests_count'] ?? 1)),
                'notes'          => $payload['notes'] ?? null,
                'estimated_time' => 15,
                'pending_started_at' => null,
                'pay_later'      => false,
                'qr_session_token' => $tableToken,
                'metadata'       => [
                    'customer_fcm_token' => $payload['fcm_token'] ?? null,
                    'payment' => [
                        'provider' => 'pakasir',
                        'method'   => 'qris',
                        'status'   => 'pending',
                        'context'  => 'before_kitchen',
                    ],
                    'self_service' => [
                        'channel'    => 'qr_meja',
                        'table_name' => $table->name,
                    ],
                    'promo' => $this->buildPromoMetadata($pricing),
                ],
            ]);

            $this->persistOrderItems($order, $items);
            $this->promoEngineService->syncUsageDifference(
                [],
                $this->promoEngineService->extractPromoIdsFromMetadata($order->metadata ?? []),
            );

            $checkout = $this->startGatewayCheckout($order, 'before_kitchen', 'qris');

            return [
                'order' => $order->load([
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
            ->getTransactionDetail($order->order_number, $expectedAmount, $order->outlet_id)['transaction'] ?? null;

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

        if ($payload['payment_method'] === 'online_platform') {
            return $this->markOrderPaidViaPlatform($order);
        }

        return $payload['payment_method'] === 'cash'
            ? $this->markOrderPaidBeforeKitchen($order, $payload['cash_received'] ?? null)
            : $this->startGatewayCheckout($order, 'before_kitchen', $payload['payment_method']);
    }

    protected function markOrderPaidViaPlatform(Order $order): array
    {
        $order->update([
            'paid_amount' => $order->total_amount,
            'pay_later' => false,
            'metadata' => $this->mergePaymentMeta($order, [
                'provider' => 'online_platform',
                'method' => $order->source,
                'status' => 'paid',
                'context' => 'before_kitchen',
                'requested_at' => now()->toIso8601String(),
                'paid_at' => now()->toIso8601String(),
                'checkout_url' => null,
            ]),
        ]);

        return [
            'message' => 'Order online telah ditandai lunas (sudah dibayar) dan dikirim ke dapur.',
            'paymentCheckout' => null,
        ];
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

    protected function startGatewayCheckout(Order $order, string $context, string $method): array
    {
        $checkoutUrl = $this->pakasirService->buildGatewayCheckoutUrl(
            $order->order_number,
            $this->resolveGatewayAmount($order),
            route('kasir.order'),
            $method,
            $order->outlet_id,
        );

        $order->update([
            'status' => $context === 'before_kitchen' ? 'pending' : $order->status,
            'pay_later' => false,
            'metadata' => $this->mergePaymentMeta($order, [
                'provider' => 'pakasir',
                'method' => $method,
                'status' => 'pending',
                'context' => $context,
                'requested_at' => now()->toIso8601String(),
                'paid_at' => null,
                'checkout_url' => $checkoutUrl,
            ]),
        ]);

        return [
            'message' => $context === 'before_kitchen'
                ? 'Checkout ' . strtoupper($method) . ' dibuat. Order akan masuk ke dapur setelah pembayaran terkonfirmasi.'
                : 'Checkout ' . strtoupper($method) . ' dibuat. Order akan selesai otomatis setelah pembayaran terkonfirmasi.',
            'paymentCheckout' => [
                'provider' => 'pakasir',
                'method' => $method,
                'order_number' => $order->order_number,
                'amount' => $this->resolveGatewayAmount($order),
                'context' => $context,
                'payment_url' => $checkoutUrl,
            ],
        ];
    }

    protected function settleExistingOrderWithCash(Order $order, array $payload, string $context): array
    {
        if ($order->hasPendingBeforeKitchenPayment()) {
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

    protected function settleExistingOrderWithGateway(Order $order, string $context, string $method): array
    {
        return $this->startGatewayCheckout($order, $context, $method);
    }

    protected function cancelPendingGatewayIfNeeded(Order $order): void
    {
        $paymentMeta = $this->getPaymentMeta($order);
        $digitalMethods = ['qris', 'ewallet', 'debit', 'transfer'];

        if (!in_array($paymentMeta['method'] ?? null, $digitalMethods, true) || ($paymentMeta['status'] ?? null) !== 'pending') {
            return;
        }

        $transaction = $this->pakasirService
            ->getTransactionDetail($order->order_number, $this->resolveGatewayAmount($order), $order->outlet_id)['transaction'] ?? null;

        if (($transaction['status'] ?? null) === 'completed') {
            throw ValidationException::withMessages([
                'error' => 'Transaksi ' . strtoupper($paymentMeta['method']) . ' sudah dibayar. Refresh data order terlebih dahulu.',
            ]);
        }

        $this->pakasirService->cancelTransaction($order->order_number, $this->resolveGatewayAmount($order), $order->outlet_id);
    }

    protected function createOrderRecord(
        array $payload,
        User $actor,
        string $outletId,
        ?string $customerId,
        array $pricing,
        string $shiftId,
    ): Order {
        return Order::create([
            'outlet_id'       => $outletId,
            'shift_id'        => $shiftId,
            'table_id'        => in_array($payload['order_type'], ['takeaway', 'online'], true) ? null : $payload['table_id'],
            'customer_id'     => $customerId,
            'cashier_id'      => $actor->id,
            'subtotal'        => $pricing['subtotal'],
            'discount_amount' => $pricing['discount_amount'],
            'total_amount'    => $pricing['total_amount'],
            'paid_amount'     => 0,
            'status'          => 'pending',
            'source'          => $payload['source'] ?? 'kasir',
            'type'            => $payload['order_type'],
            'guests_count'    => $payload['order_type'] === 'dine_in' ? max(1, (int) ($payload['guests_count'] ?? 1)) : 1,
            'notes'           => $payload['notes'] ?? null,
            'estimated_time'  => 15,
            'pending_started_at' => now(),
            'pay_later'       => ($payload['payment_option'] ?? 'pay_later') === 'pay_later',
            'metadata'        => $this->mergeArray([], [
                'payment' => [
                    'provider' => $payload['payment_method'] === 'online_platform' ? 'online_platform' : null,
                    'method'   => $payload['payment_option'] === 'pay_now' ? ($payload['payment_method'] ?? null) : null,
                    'status'   => $payload['payment_option'] === 'pay_now' ? ($payload['payment_method'] === 'online_platform' ? 'paid' : 'pending') : 'unpaid',
                    'context'  => $payload['payment_option'] === 'pay_now' ? 'before_kitchen' : 'after_service',
                ],
                'reservation' => [
                    'id' => $payload['reservation_id'] ?? null,
                ],
                'promo' => $this->buildPromoMetadata($pricing),
            ]),
        ]);
    }

    protected function resolveReservation(
        string $outletId,
        ?string $reservationId,
        string $orderType,
        ?string $tableId,
    ): ?TableReservation {
        if (!$reservationId) {
            return null;
        }

        if ($orderType !== 'dine_in' || !$tableId) {
            throw ValidationException::withMessages([
                'reservation_id' => 'Reservasi hanya bisa dipakai untuk order dine-in.',
            ]);
        }

        $reservation = TableReservation::query()
            ->whereKey($reservationId)
            ->where('outlet_id', $outletId)
            ->where('table_id', $tableId)
            ->where('status', 'booked')
            ->first();

        if (!$reservation) {
            throw ValidationException::withMessages([
                'reservation_id' => 'Reservasi meja tidak valid atau sudah tidak aktif.',
            ]);
        }

        return $reservation;
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
                'category_id' => $product->category_id,
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

        if ($order->hasPendingBeforeKitchenPayment()) {
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

        // Sinkronisasi current_guests dari semua order aktif
        $this->tableManagementService->syncTableGuests($order->table_id);
        // Tandai occupied_at jika belum ada
        $this->tableManagementService->markOccupiedAt($order->table_id);

        Table::query()
            ->whereKey($order->table_id)
            ->update(['status' => 'occupied']);
    }

    protected function syncTableStatus(?string $tableId): void
    {
        if (!$tableId) {
            return;
        }

        $this->tableReservationService->syncTableStatus($tableId);
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

    protected function persistOrderItems(Order $order, array $items): void
    {
        foreach ($items as $item) {
            $order->items()->create(Arr::only($item, [
                'product_id',
                'variant_id',
                'quantity',
                'unit_price',
                'total_price',
                'notes',
            ]));
        }
    }

    protected function buildPromoMetadata(array $pricing): array
    {
        return [
            'manual_code' => $pricing['manual_code'],
            'discount_total' => $pricing['discount_amount'],
            'applied_promos' => $pricing['applied_promos'],
            'evaluated_at' => now()->toIso8601String(),
        ];
    }

    protected function calculateExistingOrderPricing(Order $order, ?string $paymentMethod, ?string $promoCode): array
    {
        $order->loadMissing(['items.product', 'customer.membership.tier']);

        $items = $order->items->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->total_price,
                'notes' => $item->notes,
                'category_id' => $item->product?->category_id,
            ];
        })->all();

        return $this->promoEngineService->calculate(
            $order->outlet_id,
            $items,
            $order->customer,
            $paymentMethod,
            $promoCode,
        );
    }

    protected function applyExistingOrderPricing(Order $order, array $pricing): void
    {
        $previousPromoIds = $this->promoEngineService->extractPromoIdsFromMetadata($order->metadata ?? []);

        $metadata = $order->metadata ?? [];
        $metadata['promo'] = $this->buildPromoMetadata($pricing);

        $order->update([
            'subtotal' => $pricing['subtotal'],
            'discount_amount' => $pricing['discount_amount'],
            'total_amount' => $pricing['total_amount'],
            'metadata' => $metadata,
        ]);

        $currentPromoIds = $this->promoEngineService->extractPromoIdsFromMetadata($metadata);
        $this->promoEngineService->syncUsageDifference($previousPromoIds, $currentPromoIds);
    }
}
