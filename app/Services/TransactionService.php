<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Table;
use App\Models\User;
use App\Repositories\TransactionRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    protected const PAYMENT_METHODS = [
        ['value' => 'cash', 'label' => 'Cash'],
        ['value' => 'qris', 'label' => 'QRIS'],
        ['value' => 'debit', 'label' => 'Debit'],
        ['value' => 'ewallet', 'label' => 'E-Wallet'],
    ];

    public function __construct(
        protected TransactionRepository $transactionRepository,
        protected PromoEngineService $promoEngineService,
        protected ShiftService $shiftService,
        protected SecurityActivityLogService $securityActivityLogService,
    ) {
    }

    public function getDashboard(User $actor, array $filters = []): array
    {
        $outletId = $this->resolveActorOutletId($actor);
        $resolvedFilters = [
            'search' => trim((string) ($filters['search'] ?? '')),
            'status' => $this->normalizeHistoryStatusFilter($filters['status'] ?? 'all'),
            'payment_method' => (string) ($filters['payment_method'] ?? 'all'),
            'start_date' => $filters['start_date'] ?? now()->startOfMonth()->toDateString(),
            'end_date' => $filters['end_date'] ?? now()->toDateString(),
        ];
        $summary = $this->transactionRepository->getSummary($outletId);

        return [
            'summary' => $summary,
            'kasbonOrders' => $this->transformKasbonOrders(
                $this->transactionRepository->getKasbonOrders($outletId),
            ),
            'preOrders' => $this->transformPreOrders(
                $this->transactionRepository->getPreOrders($outletId),
            ),
            'historyOrders' => $this->transformHistoryOrders(
                $this->transactionRepository->getHistoryOrders($outletId, $resolvedFilters),
            ),
            'filters' => $resolvedFilters,
            'referenceData' => [
                'paymentMethods' => self::PAYMENT_METHODS,
                'categories' => Category::query()
                    ->with(['products' => function ($query) use ($outletId) {
                        $query->where('outlet_id', $outletId)
                            ->where('is_active', true)
                            ->where('is_available', true)
                            ->orderBy('sort_order');
                    }, 'products.variants' => function ($query) {
                        $query->where('is_active', true);
                    }, 'products.prices' => function ($query) use ($outletId) {
                        $query->where('outlet_id', $outletId)
                            ->where('is_active', true)
                            ->orderByDesc('created_at');
                    }])
                    ->get(),
            ],
        ];
    }

    public function createPreOrder(array $payload, User $actor): void
    {
        $outletId = $this->resolveActorOutletId($actor);
        $this->shiftService->requireActiveShiftForOutlet($outletId);

        $customer = $this->resolveCustomer(
            $outletId,
            $payload['customer_name'],
            $payload['customer_phone'],
            $payload['customer_email'] ?? null,
        );
        [, $items] = $this->prepareItems($outletId, $payload['items']);
        $pricing = $this->promoEngineService->calculate(
            $outletId,
            $items,
            $customer?->loadMissing('membership.tier'),
            null,
            $payload['promo_code'] ?? null,
        );
        $downPaymentAmount = $this->resolveDownPaymentAmount(
            $pricing['total_amount'],
            (string) $payload['down_payment_type'],
            (float) $payload['down_payment_value'],
        );

        DB::transaction(function () use ($payload, $actor, $outletId, $customer, $items, $pricing, $downPaymentAmount) {
            $order = Order::create([
                'outlet_id' => $outletId,
                'shift_id' => null,
                'table_id' => null,
                'customer_id' => $customer?->id,
                'cashier_id' => $actor->id,
                'subtotal' => $pricing['subtotal'],
                'discount_amount' => $pricing['discount_amount'],
                'total_amount' => $pricing['total_amount'],
                'paid_amount' => $downPaymentAmount,
                'status' => 'pending',
                'source' => 'kasir',
                'type' => 'takeaway',
                'notes' => $payload['notes'] ?? null,
                'estimated_time' => 15,
                'pending_started_at' => null,
                'pay_later' => $downPaymentAmount < $pricing['total_amount'],
                'metadata' => [
                    'payment' => [
                        'provider' => $downPaymentAmount > 0 ? 'manual' : null,
                        'method' => $downPaymentAmount > 0 ? 'cash' : null,
                        'status' => $downPaymentAmount >= $pricing['total_amount'] ? 'paid' : ($downPaymentAmount > 0 ? 'partial' : 'unpaid'),
                        'context' => 'pre_order',
                        'paid_at' => $downPaymentAmount > 0 ? now()->toIso8601String() : null,
                    ],
                    'promo' => $this->buildPromoMetadata($pricing),
                    'pre_order' => [
                        'pickup_at' => $payload['pickup_datetime'],
                        'down_payment_type' => $payload['down_payment_type'],
                        'down_payment_value' => (float) $payload['down_payment_value'],
                        'down_payment_amount' => $downPaymentAmount,
                        'remaining_amount' => round(max(0, $pricing['total_amount'] - $downPaymentAmount), 2),
                        'created_by' => $actor->id,
                        'activated_at' => null,
                        'activated_by' => null,
                    ],
                ],
            ]);

            $this->persistOrderItems($order, $items);

            if ($downPaymentAmount > 0) {
                $this->transactionRepository->createPaymentLog([
                    'order_id' => $order->id,
                    'user_id' => $actor->id,
                    'payment_type' => 'down_payment',
                    'payment_method' => 'cash',
                    'amount' => $downPaymentAmount,
                    'before_paid_amount' => 0,
                    'after_paid_amount' => $downPaymentAmount,
                    'notes' => 'DP awal pre-order.',
                    'metadata' => [
                        'context' => 'pre_order',
                    ],
                ]);
            }
        });
    }

    public function closeAsKasbon(Order $order, array $payload, User $actor): void
    {
        $outletId = $this->resolveActorOutletId($actor);

        if ($order->outlet_id !== $outletId) {
            abort(404);
        }

        if (!$order->customer_id) {
            throw ValidationException::withMessages([
                'customer' => 'Kasbon hanya bisa untuk order yang punya data customer.',
            ]);
        }

        if ($order->hasPendingBeforeKitchenPayment() || $order->hasActivePreOrder() || in_array($order->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'order' => 'Status order ini tidak bisa ditutup sebagai kasbon.',
            ]);
        }

        if ((float) $order->paid_amount >= (float) $order->total_amount) {
            throw ValidationException::withMessages([
                'order' => 'Order ini sudah lunas.',
            ]);
        }

        $metadata = $order->metadata ?? [];
        $metadata['payment'] = array_merge($metadata['payment'] ?? [], [
            'provider' => 'manual',
            'method' => 'kasbon',
            'status' => 'partial',
            'context' => 'after_service',
            'paid_at' => null,
        ]);
        $metadata['kasbon'] = [
            'is_active' => true,
            'due_date' => $payload['due_date'] ?? null,
            'closed_at' => now()->toIso8601String(),
            'closed_by' => $actor->id,
            'remaining_amount' => round((float) $order->total_amount - (float) $order->paid_amount, 2),
            'notes' => $payload['notes'] ?? null,
        ];

        DB::transaction(function () use ($order, $actor, $payload, $metadata) {
            $beforePaidAmount = (float) $order->paid_amount;

            $order->update([
                'status' => 'completed',
                'metadata' => $metadata,
            ]);

            $this->transactionRepository->createPaymentLog([
                'order_id' => $order->id,
                'user_id' => $actor->id,
                'payment_type' => 'kasbon_close',
                'payment_method' => 'kasbon',
                'amount' => 0,
                'before_paid_amount' => $beforePaidAmount,
                'after_paid_amount' => $beforePaidAmount,
                'notes' => $payload['notes'] ?? 'Order ditutup sebagai kasbon.',
                'metadata' => [
                    'due_date' => $payload['due_date'] ?? null,
                    'remaining_amount' => round((float) $order->total_amount - $beforePaidAmount, 2),
                ],
            ]);
        });
    }

    public function payInstallment(Order $order, array $payload, User $actor): void
    {
        $outletId = $this->resolveActorOutletId($actor);

        if ($order->outlet_id !== $outletId) {
            abort(404);
        }

        if ($order->status !== 'completed' || (float) $order->paid_amount >= (float) $order->total_amount) {
            throw ValidationException::withMessages([
                'order' => 'Order ini tidak punya sisa tagihan kasbon aktif.',
            ]);
        }

        $amount = round((float) $payload['amount'], 2);
        $beforePaidAmount = (float) $order->paid_amount;
        $remaining = round((float) $order->total_amount - $beforePaidAmount, 2);

        if ($amount > $remaining) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal cicilan melebihi sisa tagihan.',
            ]);
        }

        $afterPaidAmount = round($beforePaidAmount + $amount, 2);
        $isPaidOff = $afterPaidAmount >= (float) $order->total_amount;
        $metadata = $order->metadata ?? [];
        $metadata['payment'] = array_merge($metadata['payment'] ?? [], [
            'provider' => 'manual',
            'method' => $isPaidOff ? $payload['payment_method'] : 'kasbon',
            'status' => $isPaidOff ? 'paid' : 'partial',
            'context' => 'after_service',
            'paid_at' => $isPaidOff ? now()->toIso8601String() : null,
        ]);
        $metadata['kasbon'] = array_merge($metadata['kasbon'] ?? [], [
            'is_active' => !$isPaidOff,
            'remaining_amount' => round(max(0, (float) $order->total_amount - $afterPaidAmount), 2),
            'last_installment_at' => now()->toIso8601String(),
            'last_installment_by' => $actor->id,
        ]);

        DB::transaction(function () use ($order, $payload, $actor, $amount, $beforePaidAmount, $afterPaidAmount, $metadata) {
            $order->update([
                'paid_amount' => $afterPaidAmount,
                'metadata' => $metadata,
            ]);

            $this->transactionRepository->createPaymentLog([
                'order_id' => $order->id,
                'user_id' => $actor->id,
                'payment_type' => 'installment',
                'payment_method' => $payload['payment_method'],
                'amount' => $amount,
                'before_paid_amount' => $beforePaidAmount,
                'after_paid_amount' => $afterPaidAmount,
                'notes' => $payload['notes'] ?? 'Pembayaran cicilan kasbon.',
                'metadata' => [
                    'remaining_after' => round(max(0, (float) $order->total_amount - $afterPaidAmount), 2),
                ],
            ]);
        });
    }

    public function activatePreOrder(Order $order, User $actor): void
    {
        $outletId = $this->resolveActorOutletId($actor);

        if ($order->outlet_id !== $outletId) {
            abort(404);
        }

        if (!$order->hasActivePreOrder()) {
            throw ValidationException::withMessages([
                'order' => 'Pre-order ini sudah aktif atau tidak valid lagi.',
            ]);
        }

        $this->shiftService->requireActiveShiftForOutlet($outletId);
        $order->update([
            'status' => 'pending',
            'shift_id' => null,
            'pending_started_at' => now(),
            'metadata' => array_merge($order->metadata ?? [], [
                'pre_order' => array_merge($order->metadata['pre_order'] ?? [], [
                    'activated_at' => now()->toIso8601String(),
                    'activated_by' => $actor->id,
                ]),
            ]),
        ]);

        if ($order->table_id) {
            Table::query()
                ->whereKey($order->table_id)
                ->update(['status' => 'occupied']);
        }
    }

    public function markReceipt(Order $order, array $payload, User $actor): void
    {
        $outletId = $this->resolveActorOutletId($actor);

        if ($order->outlet_id !== $outletId) {
            abort(404);
        }

        $order->update([
            'receipt_method' => $payload['receipt_method'],
            'receipt_phone' => $payload['receipt_phone'] ?? $order->customer?->phone,
            'is_printed' => $payload['receipt_method'] === 'print' ? true : $order->is_printed,
        ]);
    }

    public function getReceiptData(Order $order, User $actor): array
    {
        $outletId = $this->resolveActorOutletId($actor);

        if ($order->outlet_id !== $outletId && $actor->role?->type !== 'owner') {
            abort(404);
        }

        $order->loadMissing([
            'outlet.printerConfig',
            'table',
            'customer',
            'cashier',
            'items.product',
            'items.variant',
            'paymentLogs.user',
        ]);
        $paymentLogs = $order->paymentLogs
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'payment_type' => $log->payment_type,
                    'payment_method' => $log->payment_method,
                    'amount' => (float) $log->amount,
                    'notes' => $log->notes,
                    'created_at' => $log->created_at?->toIso8601String(),
                    'user_name' => $log->user?->name,
                ];
            })
            ->values()
            ->all();
        $remainingAmount = round(max(0, (float) $order->total_amount - (float) $order->paid_amount), 2);

        return [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'subtotal' => (float) $order->subtotal,
                'discount_amount' => (float) $order->discount_amount,
                'total_amount' => (float) $order->total_amount,
                'paid_amount' => (float) $order->paid_amount,
                'remaining_amount' => $remainingAmount,
                'receipt_method' => $order->receipt_method ?: 'print',
                'receipt_phone' => $order->receipt_phone ?: $order->customer?->phone,
                'created_at' => $order->created_at?->toIso8601String(),
                'updated_at' => $order->updated_at?->toIso8601String(),
                'payment_method' => data_get($order->metadata, 'payment.method'),
                'payment_status' => data_get($order->metadata, 'payment.status'),
                'customer' => $order->customer ? [
                    'name' => $order->customer->name,
                    'phone' => $order->customer->phone,
                ] : null,
                'cashier' => $order->cashier?->name,
                'table' => $order->table?->name,
                'pre_order' => $order->metadata['pre_order'] ?? null,
                'kasbon' => $order->metadata['kasbon'] ?? null,
                'items' => $order->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_name' => $item->product?->name ?? 'Menu tidak ditemukan',
                        'variant_name' => $item->variant?->name,
                        'quantity' => (int) $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'total_price' => (float) $item->total_price,
                    ];
                })->values()->all(),
                'payment_logs' => $paymentLogs,
            ],
            'outlet' => [
                'name' => $order->outlet?->name,
                'address' => $order->outlet?->address,
                'phone' => $order->outlet?->phone,
                'receipt_metadata' => array_merge([
                    'receipt_template' => 'classic',
                    'receipt_font' => 'sans',
                    'receipt_color' => 'mono',
                    'receipt_footer' => 'Terima kasih atas kunjungan Anda!',
                    'receipt_logo' => null
                ], $order->outlet?->printerConfig?->metadata ?? []),
            ],
            'whatsappLink' => $this->buildWhatsappReceiptLink($order, $remainingAmount),
        ];
    }

    protected function buildWhatsappReceiptLink(Order $order, float $remainingAmount): string
    {
        $phone = preg_replace('/\D+/', '', (string) ($order->receipt_phone ?: $order->customer?->phone ?: ''));
        if ($phone === '') {
            return '';
        }

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        $message = sprintf(
            "Halo %s, berikut ringkasan transaksi %s.\nTotal: Rp %s\nTerbayar: Rp %s\nSisa: Rp %s",
            $order->customer?->name ?: 'Pelanggan',
            $order->order_number,
            number_format((float) $order->total_amount, 0, ',', '.'),
            number_format((float) $order->paid_amount, 0, ',', '.'),
            number_format($remainingAmount, 0, ',', '.'),
        );

        return 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);
    }

    protected function transformHistoryOrders(Collection $orders): array
    {
        return $orders->map(function (Order $order) {
            $remainingAmount = round(max(0, (float) $order->total_amount - (float) $order->paid_amount), 2);

            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'service_label' => $order->type === 'takeaway' ? 'Takeaway' : ($order->table?->name ?: 'Dine In'),
                'customer_name' => $order->customer?->name ?: 'Walk-in',
                'customer_phone' => $order->customer?->phone,
                'cashier_name' => $order->cashier?->name,
                'subtotal' => (float) $order->subtotal,
                'discount_amount' => (float) $order->discount_amount,
                'total_amount' => (float) $order->total_amount,
                'paid_amount' => (float) $order->paid_amount,
                'remaining_amount' => $remainingAmount,
                'payment_method' => data_get($order->metadata, 'payment.method'),
                'payment_status' => data_get($order->metadata, 'payment.status'),
                'receipt_method' => $order->receipt_method,
                'created_at' => $order->created_at?->toIso8601String(),
                'updated_at' => $order->updated_at?->toIso8601String(),
                'items_count' => $order->items->count(),
                'payment_logs_count' => $order->paymentLogs->count(),
                'has_kasbon' => $remainingAmount > 0,
            ];
        })->values()->all();
    }

    protected function transformKasbonOrders(Collection $orders): array
    {
        return $orders->map(function (Order $order) {
            $remainingAmount = round(max(0, (float) $order->total_amount - (float) $order->paid_amount), 2);
            $lastPayment = $order->paymentLogs->sortByDesc('created_at')->first();

            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer?->name ?: 'Customer',
                'customer_phone' => $order->customer?->phone,
                'cashier_name' => $order->cashier?->name,
                'total_amount' => (float) $order->total_amount,
                'paid_amount' => (float) $order->paid_amount,
                'remaining_amount' => $remainingAmount,
                'due_date' => data_get($order->metadata, 'kasbon.due_date'),
                'closed_at' => data_get($order->metadata, 'kasbon.closed_at'),
                'last_payment' => $lastPayment ? [
                    'amount' => (float) $lastPayment->amount,
                    'method' => $lastPayment->payment_method,
                    'created_at' => $lastPayment->created_at?->toIso8601String(),
                ] : null,
            ];
        })->values()->all();
    }

    protected function transformPreOrders(Collection $orders): array
    {
        return $orders->map(function (Order $order) {
            $pickupAt = data_get($order->metadata, 'pre_order.pickup_at');
            $remainingAmount = round(max(0, (float) $order->total_amount - (float) $order->paid_amount), 2);

            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer?->name ?: 'Customer',
                'customer_phone' => $order->customer?->phone,
                'pickup_at' => $pickupAt,
                'subtotal' => (float) $order->subtotal,
                'discount_amount' => (float) $order->discount_amount,
                'total_amount' => (float) $order->total_amount,
                'paid_amount' => (float) $order->paid_amount,
                'remaining_amount' => $remainingAmount,
                'items_count' => $order->items->count(),
                'dp_rule' => data_get($order->metadata, 'pre_order.down_payment_type'),
                'dp_value' => data_get($order->metadata, 'pre_order.down_payment_value'),
                'dp_amount' => (float) data_get($order->metadata, 'pre_order.down_payment_amount', 0),
            ];
        })->values()->all();
    }

    protected function resolveActorOutletId(User $actor): string
    {
        if (!$actor->outlet_id) {
            throw ValidationException::withMessages([
                'outlet' => 'User belum terhubung ke outlet aktif.',
            ]);
        }

        return $actor->outlet_id;
    }

    protected function normalizeHistoryStatusFilter(mixed $status): string
    {
        $value = (string) $status;

        return in_array($value, ['all', 'completed', 'cancelled'], true)
            ? $value
            : 'all';
    }

    protected function resolveCustomer(
        string $outletId,
        string $customerName,
        string $customerPhone,
        ?string $customerEmail,
    ): Customer {
        $customer = Customer::firstOrNew([
            'outlet_id' => $outletId,
            'phone' => $customerPhone,
        ]);

        $customer->name = $customerName;
        $customer->email = $customerEmail ?: $customer->email;
        $customer->is_active = true;
        $customer->registered_via = $customer->exists ? $customer->registered_via : 'pre_order';
        $customer->save();

        return $customer;
    }

    protected function prepareItems(string $outletId, array $payloadItems): array
    {
        $productIds = collect($payloadItems)->pluck('product_id')->unique()->values()->all();
        $products = $this->transactionRepository->getProducts($outletId, $productIds);
        $variantIds = collect($payloadItems)->pluck('variant_id')->filter()->unique()->values()->all();
        $variants = $this->transactionRepository->getVariants($variantIds);
        $items = [];
        $subtotal = 0.0;

        foreach ($payloadItems as $item) {
            $product = $products->get($item['product_id']);
            if (!$product) {
                throw ValidationException::withMessages([
                    'items' => 'Ada item pre-order yang tidak valid atau sudah tidak tersedia.',
                ]);
            }

            $variantId = $item['variant_id'] ?? null;
            $variant = null;
            if ($variantId) {
                $variant = $variants->get($variantId);
                if (!$variant || $variant->product_id !== $product->id) {
                    throw ValidationException::withMessages([
                        'items' => 'Varian pre-order tidak valid untuk produk yang dipilih.',
                    ]);
                }
            }

            $quantity = (int) $item['quantity'];
            $basePrice = (float) ($product->prices->first()?->price ?? $product->base_price ?? 0);
            $variantPrice = (float) ($variant?->additional_price ?? 0);
            $unitPrice = $basePrice + $variantPrice;
            $lineTotal = $unitPrice * $quantity;
            $subtotal += $lineTotal;

            $items[] = [
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $lineTotal,
                'notes' => $item['notes'] ?? null,
                'category_id' => $product->category_id,
            ];
        }

        return [$subtotal, $items];
    }

    protected function resolveDownPaymentAmount(float $totalAmount, string $type, float $value): float
    {
        if ($totalAmount <= 0) {
            return 0;
        }

        if ($type === 'percentage') {
            return round(max(0, min(100, $value)) / 100 * $totalAmount, 2);
        }

        return round(min($totalAmount, max(0, $value)), 2);
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
}
