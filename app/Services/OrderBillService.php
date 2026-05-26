<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OrderBillService
{
    public function __construct(
        protected PromoEngineService $promoEngineService,
    ) {
    }

    public function splitOrder(Order $order, array $payload, User $actor): void
    {
        $order->loadMissing(['items.product', 'customer.membership.tier']);

        $this->guardEditableOrder($order, $actor, $payload['approval_pin'] ?? null);

        $splitMap = collect($payload['item_splits'])
            ->keyBy('order_item_id')
            ->map(fn ($item) => (int) $item['quantity']);

        $sourceItems = [];
        $newOrderItems = [];

        foreach ($order->items as $item) {
            $moveQty = min($splitMap->get($item->id, 0), (int) $item->quantity);
            $remainingQty = (int) $item->quantity - $moveQty;

            if ($remainingQty > 0) {
                $sourceItems[] = $this->buildOrderItemPayload($item, $remainingQty);
            }

            if ($moveQty > 0) {
                $newOrderItems[] = $this->buildOrderItemPayload($item, $moveQty);
            }
        }

        if (count($newOrderItems) === 0) {
            throw ValidationException::withMessages([
                'error' => 'Pilih minimal satu item untuk dipindahkan ke bill kedua.',
            ]);
        }

        if (count($sourceItems) === 0) {
            throw ValidationException::withMessages([
                'error' => 'Bill asal tidak boleh kosong. Sisakan minimal satu item.',
            ]);
        }

        $order->loadMissing('customer.membership.tier');
        $previousPromoIds = $this->promoEngineService->extractPromoIdsFromMetadata($order->metadata ?? []);
        $paymentStatus = data_get($order->metadata, 'payment.status');
        $paymentMethod = $paymentStatus === 'paid'
            ? data_get($order->metadata, 'payment.method')
            : null;
        $sourcePricing = $this->promoEngineService->calculate(
            $order->outlet_id,
            $sourceItems,
            $order->customer,
            $paymentMethod,
            data_get($order->metadata, 'promo.manual_code'),
        );
        $newOrderPricing = $this->promoEngineService->calculate(
            $order->outlet_id,
            $newOrderItems,
            $order->customer,
            $paymentMethod,
            null,
        );

        DB::transaction(function () use (
            $order,
            $actor,
            $sourceItems,
            $newOrderItems,
            $sourcePricing,
            $newOrderPricing,
            $previousPromoIds
        ) {
            $newOrder = Order::create([
                'outlet_id' => $order->outlet_id,
                'shift_id' => $order->shift_id,
                'table_id' => $order->table_id,
                'customer_id' => $order->customer_id,
                'cashier_id' => $actor->id,
                'source' => $order->source,
                'type' => $order->type,
                'status' => 'pending',
                'subtotal' => $newOrderPricing['subtotal'],
                'discount_amount' => $newOrderPricing['discount_amount'],
                'total_amount' => $newOrderPricing['total_amount'],
                'paid_amount' => 0,
                'notes' => $order->notes,
                'estimated_time' => $order->estimated_time,
                'pending_started_at' => now(),
                'pay_later' => $order->pay_later,
                'metadata' => array_merge($order->metadata ?? [], [
                    'split_from_order_id' => $order->id,
                    'split_at' => now()->toIso8601String(),
                    'promo' => $this->buildPromoMetadata($newOrderPricing),
                ]),
            ]);

            $this->persistOrderItems($newOrder, $newOrderItems);

            $order->items()->delete();

            $this->persistOrderItems($order, $sourceItems);

            $updatedSourceMetadata = array_merge($order->metadata ?? [], [
                'split_child_order_id' => $newOrder->id,
                'split_at' => now()->toIso8601String(),
                'promo' => $this->buildPromoMetadata($sourcePricing),
            ]);

            $order->update([
                'subtotal' => $sourcePricing['subtotal'],
                'discount_amount' => $sourcePricing['discount_amount'],
                'total_amount' => $sourcePricing['total_amount'],
                'status' => 'pending',
                'cooking_started_at' => null,
                'pending_started_at' => now(),
                'metadata' => $updatedSourceMetadata,
            ]);

            $currentPromoIds = array_merge(
                $this->promoEngineService->extractPromoIdsFromMetadata($updatedSourceMetadata),
                $this->promoEngineService->extractPromoIdsFromMetadata($newOrder->metadata ?? []),
            );
            $this->promoEngineService->syncUsageDifference($previousPromoIds, $currentPromoIds);
        });
    }

    public function mergeOrders(array $orderIds, ?string $approvalPin, User $actor): void
    {
        $orders = Order::query()
            ->with(['items.product', 'customer.membership.tier'])
            ->whereIn('id', $orderIds)
            ->get();

        if ($orders->count() < 2) {
            throw ValidationException::withMessages([
                'error' => 'Pilih minimal dua order untuk digabung.',
            ]);
        }

        if ($orders->contains(fn (Order $order) => $order->outlet_id !== $actor->outlet_id)) {
            throw ValidationException::withMessages([
                'error' => 'Ada order yang tidak berada di outlet aktif Anda.',
            ]);
        }

        $tableIds = $orders->pluck('table_id')->filter()->unique();
        if ($tableIds->count() !== 1 || $orders->contains(fn (Order $order) => empty($order->table_id))) {
            throw ValidationException::withMessages([
                'error' => 'Gabung bill hanya bisa untuk order aktif pada meja yang sama.',
            ]);
        }

        foreach ($orders as $order) {
            $this->guardEditableOrder($order, $actor, $approvalPin);
        }

        $baseOrder = $orders->sortBy('created_at')->first();
        $combinedItems = $this->combineOrderItems($orders);
        $uniqueCustomerIds = $orders->pluck('customer_id')->filter()->unique();
        $mergedNotes = $orders
            ->pluck('notes')
            ->filter()
            ->unique()
            ->implode(' | ');

        $previousPromoIds = $orders
            ->flatMap(fn (Order $order) => $this->promoEngineService->extractPromoIdsFromMetadata($order->metadata ?? []))
            ->values()
            ->all();
        $manualCodes = $orders
            ->map(fn (Order $order) => data_get($order->metadata, 'promo.manual_code'))
            ->filter()
            ->unique()
            ->values();
        $paymentMethods = $orders
            ->map(function (Order $order) {
                return data_get($order->metadata, 'payment.status') === 'paid'
                    ? data_get($order->metadata, 'payment.method')
                    : null;
            })
            ->filter()
            ->unique()
            ->values();
        $mergedCustomer = $uniqueCustomerIds->count() === 1 ? $baseOrder->customer : null;
        $mergedPricing = $this->promoEngineService->calculate(
            $baseOrder->outlet_id,
            $combinedItems,
            $mergedCustomer,
            $paymentMethods->count() === 1 ? $paymentMethods->first() : null,
            $manualCodes->count() === 1 ? $manualCodes->first() : null,
        );

        DB::transaction(function () use (
            $orders,
            $baseOrder,
            $actor,
            $combinedItems,
            $uniqueCustomerIds,
            $mergedNotes,
            $mergedPricing,
            $previousPromoIds
        ) {
            $mergedOrder = Order::create([
                'outlet_id' => $baseOrder->outlet_id,
                'shift_id' => $baseOrder->shift_id,
                'table_id' => $baseOrder->table_id,
                'customer_id' => $uniqueCustomerIds->count() === 1 ? $uniqueCustomerIds->first() : null,
                'cashier_id' => $actor->id,
                'source' => $baseOrder->source,
                'type' => $baseOrder->type,
                'status' => 'pending',
                'subtotal' => $mergedPricing['subtotal'],
                'discount_amount' => $mergedPricing['discount_amount'],
                'total_amount' => $mergedPricing['total_amount'],
                'paid_amount' => 0,
                'notes' => $mergedNotes ?: null,
                'estimated_time' => $baseOrder->estimated_time,
                'pending_started_at' => now(),
                'pay_later' => $baseOrder->pay_later,
                'metadata' => array_merge($baseOrder->metadata ?? [], [
                    'merged_from_order_ids' => $orders->pluck('id')->values()->all(),
                    'merged_at' => now()->toIso8601String(),
                    'promo' => $this->buildPromoMetadata($mergedPricing),
                ]),
            ]);

            $this->persistOrderItems($mergedOrder, $combinedItems);

            foreach ($orders as $order) {
                $order->update([
                    'status' => 'cancelled',
                    'metadata' => array_merge($order->metadata ?? [], [
                        'merged_into_order_id' => $mergedOrder->id,
                        'merged_at' => now()->toIso8601String(),
                    ]),
                ]);
            }

            $currentPromoIds = $this->promoEngineService->extractPromoIdsFromMetadata($mergedOrder->metadata ?? []);
            $this->promoEngineService->syncUsageDifference($previousPromoIds, $currentPromoIds);
        });
    }

    protected function guardEditableOrder(Order $order, User $actor, ?string $approvalPin): void
    {
        if ($order->outlet_id !== $actor->outlet_id) {
            throw ValidationException::withMessages([
                'error' => 'Order ini tidak berada di outlet aktif Anda.',
            ]);
        }

        if (in_array($order->status, ['payment_pending', 'ready', 'waiting_bar_approval', 'delivered', 'completed', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'error' => 'Order dengan status ini tidak bisa diproses untuk split/gabung bill.',
            ]);
        }

        if ($order->status === 'in_progress') {
            $this->validateSupervisorApproval($order->outlet_id, $approvalPin);
        }
    }

    protected function validateSupervisorApproval(?string $outletId, ?string $approvalPin): void
    {
        if (!$approvalPin) {
            throw ValidationException::withMessages([
                'approval_pin' => 'Aksi ini butuh PIN approval supervisor karena order sedang dimasak.',
            ]);
        }

        $approvers = User::query()
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->whereHas('role', function ($query) {
                $query->whereIn('type', ['supervisor', 'owner']);
            })
            ->get();

        $isValid = $approvers->contains(function (User $user) use ($approvalPin) {
            return $user->approval_pin && Hash::check($approvalPin, $user->approval_pin);
        });

        if (!$isValid) {
            throw ValidationException::withMessages([
                'approval_pin' => 'PIN approval supervisor tidak valid.',
            ]);
        }
    }

    protected function buildOrderItemPayload(OrderItem $item, int $quantity): array
    {
        return [
            'product_id' => $item->product_id,
            'variant_id' => $item->variant_id,
            'quantity' => $quantity,
            'unit_price' => (float) $item->unit_price,
            'total_price' => (float) $item->unit_price * $quantity,
            'notes' => $item->notes,
            'category_id' => $item->product?->category_id,
        ];
    }

    protected function combineOrderItems(Collection $orders): array
    {
        $bucket = [];

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $key = implode(':', [
                    $item->product_id,
                    $item->variant_id ?? 'base',
                    $item->notes ?? 'no-note',
                    (float) $item->unit_price,
                ]);

                if (!isset($bucket[$key])) {
                    $bucket[$key] = $this->buildOrderItemPayload($item, 0);
                    $bucket[$key]['quantity'] = 0;
                    $bucket[$key]['total_price'] = 0;
                }

                $bucket[$key]['quantity'] += (int) $item->quantity;
                $bucket[$key]['total_price'] =
                    $bucket[$key]['quantity'] * $bucket[$key]['unit_price'];
            }
        }

        return array_values($bucket);
    }

    protected function sumItems(array $items): float
    {
        return (float) collect($items)->sum('total_price');
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
