<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderPaymentLog;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TransactionRepository
{
    public function getHistoryOrders(string $outletId, array $filters = [], int $limit = 18): Collection
    {
        return Order::query()
            ->where('outlet_id', $outletId)
            ->whereIn('status', ['completed', 'cancelled'])
            ->when(($filters['search'] ?? '') !== '', function (Builder $query) use ($filters) {
                $search = trim((string) $filters['search']);
                $query->where(function (Builder $innerQuery) use ($search) {
                    $innerQuery
                        ->where('order_number', 'ilike', '%' . $search . '%')
                        ->orWhereHas('customer', function (Builder $customerQuery) use ($search) {
                            $customerQuery
                                ->where('name', 'ilike', '%' . $search . '%')
                                ->orWhere('phone', 'ilike', '%' . $search . '%');
                        });
                });
            })
            ->when(($filters['status'] ?? 'all') !== 'all', fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(($filters['payment_method'] ?? 'all') !== 'all', function (Builder $query) use ($filters) {
                $paymentMethod = (string) $filters['payment_method'];
                if ($paymentMethod === 'kasbon') {
                    $query->whereColumn('paid_amount', '<', 'total_amount');
                    return;
                }

                $query->where('metadata->payment->method', $paymentMethod);
            })
            ->when($filters['start_date'] ?? null, fn (Builder $query, string $startDate) => $query->whereDate('created_at', '>=', $startDate))
            ->when($filters['end_date'] ?? null, fn (Builder $query, string $endDate) => $query->whereDate('created_at', '<=', $endDate))
            ->with([
                'table:id,name',
                'customer:id,name,phone,email',
                'cashier:id,name',
                'items.product:id,name',
                'items.variant:id,name',
                'paymentLogs.user:id,name',
            ])
            ->latest('updated_at')
            ->limit($limit)
            ->get();
    }

    public function getKasbonOrders(string $outletId, int $limit = 12): Collection
    {
        return Order::query()
            ->where('outlet_id', $outletId)
            ->where('status', 'completed')
            ->whereColumn('paid_amount', '<', 'total_amount')
            ->with([
                'customer:id,name,phone,email',
                'cashier:id,name',
                'paymentLogs.user:id,name',
            ])
            ->orderByRaw("(metadata->'kasbon'->>'due_date') asc nulls last")
            ->latest('updated_at')
            ->limit($limit)
            ->get();
    }

    public function getPreOrders(string $outletId, int $limit = 12): Collection
    {
        return Order::query()
            ->where('outlet_id', $outletId)
            ->where('status', 'scheduled')
            ->with([
                'customer:id,name,phone,email',
                'cashier:id,name',
                'items.product:id,name',
                'items.variant:id,name',
                'paymentLogs.user:id,name',
            ])
            ->orderBy('metadata->pre_order->pickup_at')
            ->limit($limit)
            ->get();
    }

    public function getSummary(string $outletId): array
    {
        $historyBase = Order::query()
            ->where('outlet_id', $outletId)
            ->whereIn('status', ['completed', 'cancelled']);

        $kasbonBase = Order::query()
            ->where('outlet_id', $outletId)
            ->where('status', 'completed')
            ->whereColumn('paid_amount', '<', 'total_amount');

        $preOrderBase = Order::query()
            ->where('outlet_id', $outletId)
            ->where('status', 'scheduled');

        return [
            'history_count' => (clone $historyBase)->count(),
            'completed_today' => (clone $historyBase)->where('status', 'completed')->whereDate('updated_at', now()->toDateString())->count(),
            'kasbon_count' => (clone $kasbonBase)->count(),
            'kasbon_outstanding' => (float) ((clone $kasbonBase)->sum(\DB::raw('total_amount - paid_amount')) ?? 0),
            'preorder_count' => (clone $preOrderBase)->count(),
            'preorder_dp_collected' => (float) ((clone $preOrderBase)->sum('paid_amount') ?? 0),
        ];
    }

    public function createPaymentLog(array $payload): OrderPaymentLog
    {
        return OrderPaymentLog::query()->create($payload);
    }

    public function getProducts(string $outletId, array $productIds): Collection
    {
        return Product::query()
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
    }

    public function getVariants(array $variantIds): Collection
    {
        return ProductVariant::query()
            ->whereIn('id', $variantIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');
    }
}
