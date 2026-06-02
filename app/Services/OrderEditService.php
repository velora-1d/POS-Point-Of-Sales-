<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OrderEditService
{
    public function __construct(
        protected PromoEngineService $promoEngineService,
        protected ApprovalRuleService $approvalRuleService,
    ) {
    }

    public function updateOrder(Order $order, array $payload, User $actor): void
    {
        if ($order->outlet_id !== $actor->outlet_id) {
            throw ValidationException::withMessages([
                'error' => 'Order ini tidak berada di outlet aktif Anda.',
            ]);
        }

        if ($order->hasPendingBeforeKitchenPayment() || in_array($order->status, ['ready', 'waiting_bar_approval', 'delivered', 'completed', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'error' => 'Order dengan status ini tidak bisa diedit lagi.',
            ]);
        }

        $items = $this->prepareItems($order, $payload['items']);
        $previousPromoIds = $this->promoEngineService->extractPromoIdsFromMetadata($order->metadata ?? []);
        $paymentStatus = data_get($order->metadata, 'payment.status');
        $paymentMethod = $paymentStatus === 'paid'
            ? data_get($order->metadata, 'payment.method')
            : null;
        $pricing = $this->promoEngineService->calculate(
            $order->outlet_id,
            $items,
            $order->loadMissing('customer.membership.tier')->customer,
            $paymentMethod,
            data_get($order->metadata, 'promo.manual_code'),
        );
        $requiresOwnerApproval = $this->approvalRuleService->assertOrderEditApproval(
            $order,
            (float) $pricing['total_amount'],
            $payload['approval_pin'] ?? null,
        );

        if ($order->status === 'in_progress' && !$requiresOwnerApproval) {
            $this->validateSupervisorApproval($order->outlet_id, $payload['approval_pin'] ?? null);
        }

        $metadata = array_merge($order->metadata ?? [], [
            'last_internal_edit' => [
                'edited_by' => $actor->id,
                'edited_at' => now()->toIso8601String(),
                'required_supervisor_approval' => $order->status === 'in_progress' && !$requiresOwnerApproval,
                'required_owner_approval' => $requiresOwnerApproval,
            ],
        ]);
        $metadata['promo'] = $this->buildPromoMetadata($pricing);

        DB::transaction(function () use ($order, $payload, $items, $pricing, $metadata, $previousPromoIds) {
            $order->items()->delete();

            $this->persistOrderItems($order, $items);

            $order->update([
                'subtotal' => $pricing['subtotal'],
                'discount_amount' => $pricing['discount_amount'],
                'total_amount' => $pricing['total_amount'],
                'notes' => $payload['notes'] ?? null,
                'status' => 'pending',
                'cooking_started_at' => null,
                'pending_started_at' => now(),
                'metadata' => $metadata,
            ]);

            $currentPromoIds = $this->promoEngineService->extractPromoIdsFromMetadata($metadata);
            $this->promoEngineService->syncUsageDifference($previousPromoIds, $currentPromoIds);
        });
    }

    protected function validateSupervisorApproval(?string $outletId, ?string $approvalPin): void
    {
        if (!$approvalPin) {
            throw ValidationException::withMessages([
                'approval_pin' => 'Order yang sedang dimasak butuh PIN approval supervisor.',
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

    protected function prepareItems(Order $order, array $payloadItems): array
    {
        $productIds = collect($payloadItems)->pluck('product_id')->unique()->values();
        $products = Product::query()
            ->where('outlet_id', $order->outlet_id)
            ->where('is_active', true)
            ->where('is_available', true)
            ->whereIn('id', $productIds)
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

        return collect($payloadItems)->map(function (array $item) use ($products, $variants) {
            $product = $products->get($item['product_id']);

            if (!$product) {
                throw ValidationException::withMessages([
                    'error' => 'Ada item edit yang tidak valid atau sudah tidak tersedia.',
                ]);
            }

            $variantId = $item['variant_id'] ?? null;
            if ($variantId) {
                $variant = $variants->get($variantId);
                if (!$variant || $variant->product_id !== $product->id) {
                    throw ValidationException::withMessages([
                        'error' => 'Varian produk pada edit order tidak valid.',
                    ]);
                }
            }

            $unitPrice = (float) $item['unit_price'];
            $quantity = (int) $item['quantity'];

            return [
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $unitPrice * $quantity,
                'notes' => $item['notes'] ?? null,
                'category_id' => $product->category_id,
            ];
        })->all();
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

    public function cancelOrder(Order $order, User $actor): void
    {
        if ($order->outlet_id !== $actor->outlet_id) {
            throw ValidationException::withMessages([
                'error' => 'Order ini tidak berada di outlet aktif Anda.',
            ]);
        }

        if (in_array($order->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'error' => 'Order dengan status ini tidak bisa dibatalkan.',
            ]);
        }

        DB::transaction(function () use ($order, $actor) {
            $order->update([
                'status' => 'cancelled',
                'metadata' => array_merge($order->metadata ?? [], [
                    'cancelled_by' => $actor->id,
                    'cancelled_at' => now()->toIso8601String(),
                ]),
            ]);
        });
    }
}
