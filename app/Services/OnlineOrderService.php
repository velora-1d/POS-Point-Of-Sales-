<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Repositories\OnlineOrderRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OnlineOrderService
{
    public function __construct(
        protected OnlineOrderRepository $onlineOrderRepository,
        protected OnlineOrderStatusSyncService $onlineOrderStatusSyncService,
    ) {
    }

    public function getDashboard(User $actor, array $filters = []): array
    {
        $this->assertCanRead($actor);

        if ($actor->role?->type !== 'owner' && !$actor->outlet_id) {
            throw ValidationException::withMessages([
                'outlet_id' => 'User belum terhubung ke outlet aktif.',
            ]);
        }

        $scopeOutletId = $actor->role?->type === 'owner'
            ? ($filters['outlet_id'] ?? ($actor->outlet_id ?: null))
            : $actor->outlet_id;
        $resolvedFilters = [
            'platform' => $filters['platform'] ?? '',
            'status' => $filters['status'] ?? '',
            'outlet_id' => $scopeOutletId ?? '',
            'start_date' => $filters['start_date'] ?? now()->startOfMonth()->toDateString(),
            'end_date' => $filters['end_date'] ?? now()->toDateString(),
            'per_page' => (int) ($filters['per_page'] ?? 12),
        ];

        return [
            'summary' => $this->onlineOrderRepository->getSummary($resolvedFilters, $scopeOutletId),
            'orders' => $this->transformPaginator(
                $this->onlineOrderRepository->paginate($resolvedFilters, $scopeOutletId),
            ),
            'history' => $this->transformHistoryLogs(
                $this->onlineOrderRepository->getRecentHistoryLogs($resolvedFilters, $scopeOutletId),
            ),
            'filters' => $resolvedFilters,
            'referenceData' => [
                'outlets' => $this->onlineOrderRepository->getOutlets(
                    $actor->role?->type === 'owner' ? null : $scopeOutletId,
                ),
            ],
        ];
    }

    public function receiveWebhook(string $platform, array $payload): array
    {
        $normalizedPlatform = $this->normalizePlatform($platform);
        $outletId = (string) $payload['outlet_id'];
        $existingOrder = $this->onlineOrderRepository->findExistingByExternal(
            $outletId,
            $normalizedPlatform,
            (string) $payload['external_order_id'],
        );

        if ($existingOrder) {
            return [
                'order_id' => $existingOrder->id,
                'order_number' => $existingOrder->order_number,
                'created' => false,
            ];
        }

        return DB::transaction(function () use ($normalizedPlatform, $payload, $outletId) {
            $preparedItems = $this->prepareItems($outletId, $payload['items']);
            $subtotal = $payload['subtotal'] ?? array_sum(array_column($preparedItems, 'total_price'));
            $discountAmount = (float) ($payload['discount_amount'] ?? 0);
            $totalAmount = (float) ($payload['total_amount'] ?? max(0, ((float) $subtotal) - $discountAmount));
            $customer = $this->resolveCustomer($outletId, $payload);

            $order = $this->onlineOrderRepository->createOrder([
                'outlet_id' => $outletId,
                'table_id' => null,
                'customer_id' => $customer?->id,
                'cashier_id' => null,
                'source' => $normalizedPlatform,
                'type' => 'online',
                'status' => 'pending',
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => $totalAmount,
                'notes' => $payload['notes'] ?? null,
                'estimated_time' => (int) ($payload['estimated_time'] ?? 20),
                'pending_started_at' => $payload['ordered_at'] ?? now(),
                'external_order_id' => (string) $payload['external_order_id'],
                'external_platform' => $normalizedPlatform,
                'pay_later' => false,
                'metadata' => [
                    'payment' => [
                        'provider' => $normalizedPlatform,
                        'method' => $normalizedPlatform,
                        'status' => $payload['payment_status'] ?? 'paid',
                        'context' => 'before_kitchen',
                        'paid_at' => now()->toIso8601String(),
                    ],
                    'online_order' => [
                        'platform' => $normalizedPlatform,
                        'received_at' => now()->toIso8601String(),
                    ],
                    'external_payload' => $payload['metadata'] ?? [],
                ],
            ]);

            foreach ($preparedItems as $item) {
                $this->onlineOrderRepository->createOrderItem($order, $item);
            }

            $this->onlineOrderRepository->createStatusLog([
                'order_id' => $order->id,
                'from_status' => null,
                'to_status' => 'pending',
                'changed_by' => null,
                'changed_by_type' => 'system',
                'notes' => 'Order online diterima dari '.strtoupper($normalizedPlatform).'.',
                'created_at' => now(),
            ]);

            $this->onlineOrderStatusSyncService->sync(
                $order,
                'pending',
                'Order online diterima sistem dan menunggu diproses kitchen.',
            );

            return [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'created' => true,
            ];
        });
    }

    protected function prepareItems(string $outletId, array $payloadItems): array
    {
        $productIds = collect($payloadItems)
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
        $products = $this->onlineOrderRepository->getProducts($outletId, $productIds);
        $variantIds = collect($payloadItems)
            ->pluck('variant_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
        $variants = $this->onlineOrderRepository->getVariants($variantIds);
        $items = [];

        foreach ($payloadItems as $item) {
            $product = $products->get($item['product_id']);
            if (!$product) {
                throw ValidationException::withMessages([
                    'items' => 'Ada produk order online yang tidak valid untuk outlet ini.',
                ]);
            }

            $variantId = $item['variant_id'] ?? null;
            $variant = null;
            if ($variantId) {
                $variant = $variants->get($variantId);
                if (!$variant || $variant->product_id !== $product->id) {
                    throw ValidationException::withMessages([
                        'items' => 'Varian order online tidak sesuai dengan produk yang dipilih.',
                    ]);
                }
            }

            $unitPrice = array_key_exists('unit_price', $item) && $item['unit_price'] !== null
                ? (float) $item['unit_price']
                : (float) (($product->prices->first()?->price ?? $product->base_price ?? 0) + (float) ($variant?->additional_price ?? 0));
            $quantity = (int) $item['quantity'];

            $items[] = [
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $unitPrice * $quantity,
                'notes' => $item['notes'] ?? null,
            ];
        }

        return $items;
    }

    protected function resolveCustomer(string $outletId, array $payload): ?\App\Models\Customer
    {
        $phone = $payload['customer_phone'] ?? null;
        $email = $payload['customer_email'] ?? null;
        $existingCustomer = ($phone || $email)
            ? $this->onlineOrderRepository->findCustomerByIdentity($outletId, $phone, $email)
            : null;

        if ($existingCustomer) {
            return $existingCustomer;
        }

        return $this->onlineOrderRepository->createCustomer([
            'outlet_id' => $outletId,
            'name' => trim((string) $payload['customer_name']),
            'phone' => $phone,
            'email' => $email,
            'registered_via' => 'online_order',
            'is_active' => true,
        ]);
    }

    protected function transformPaginator(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        $paginator->setCollection(
            $paginator->getCollection()->map(fn (Order $order) => $this->transformOrder($order)),
        );

        return $paginator;
    }

    protected function transformOrder(Order $order): array
    {
        $latestOnlineSync = data_get($order->metadata, 'online_sync.latest', []);
        $onlineSyncHistory = data_get($order->metadata, 'online_sync.history', []);

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'source' => $order->source,
            'external_order_id' => $order->external_order_id,
            'external_platform' => $order->external_platform,
            'subtotal' => (float) $order->subtotal,
            'discount_amount' => (float) $order->discount_amount,
            'total_amount' => (float) $order->total_amount,
            'paid_amount' => (float) $order->paid_amount,
            'notes' => $order->notes,
            'estimated_time' => (int) ($order->estimated_time ?? 20),
            'created_at' => $order->created_at?->toIso8601String(),
            'updated_at' => $order->updated_at?->toIso8601String(),
            'customer' => $order->customer ? [
                'id' => $order->customer->id,
                'name' => $order->customer->name,
                'phone' => $order->customer->phone,
            ] : null,
            'outlet' => $order->outlet ? [
                'id' => $order->outlet->id,
                'name' => $order->outlet->name,
            ] : null,
            'online_sync' => [
                'internal_status' => $latestOnlineSync['internal_status'] ?? null,
                'platform_status' => $latestOnlineSync['platform_status'] ?? null,
                'platform' => $latestOnlineSync['platform'] ?? $order->external_platform,
                'transport' => $latestOnlineSync['transport'] ?? null,
                'synced_at' => $latestOnlineSync['synced_at'] ?? null,
                'notes' => $latestOnlineSync['notes'] ?? null,
                'history_count' => count($onlineSyncHistory),
            ],
            'items' => $order->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_name' => $item->product?->name ?? 'Menu tidak ditemukan',
                    'variant_name' => $item->variant?->name,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'total_price' => (float) $item->total_price,
                    'notes' => $item->notes,
                ];
            })->values()->all(),
        ];
    }

    protected function transformHistoryLogs(Collection $logs): array
    {
        return $logs->map(function (\App\Models\OrderStatusLog $log) {
            return [
                'id' => $log->id,
                'order_number' => $log->order?->order_number,
                'external_order_id' => $log->order?->external_order_id,
                'platform' => $log->order?->external_platform ?: $log->order?->source,
                'current_status' => $log->order?->status,
                'from_status' => $log->from_status,
                'to_status' => $log->to_status,
                'changed_by_name' => $log->changer?->name ?? 'System',
                'changed_by_type' => $log->changed_by_type ?? 'system',
                'notes' => $log->notes,
                'created_at' => $log->created_at?->toIso8601String(),
                'customer_name' => $log->order?->customer?->name,
                'outlet_name' => $log->order?->outlet?->name,
                'sync_history_count' => count(data_get($log->order?->metadata, 'online_sync.history', [])),
            ];
        })->values()->all();
    }

    protected function normalizePlatform(string $platform): string
    {
        $normalized = strtolower(trim($platform));

        if (!in_array($normalized, ['gofood', 'grabfood'], true)) {
            throw ValidationException::withMessages([
                'platform' => 'Platform order online tidak didukung.',
            ]);
        }

        return $normalized;
    }

    protected function assertCanRead(User $actor): void
    {
        if (!in_array($actor->role?->type, ['owner', 'supervisor', 'kasir', 'kitchen', 'bar'], true)) {
            abort(403, 'Menu order online tidak tersedia untuk role ini.');
        }
    }
}
