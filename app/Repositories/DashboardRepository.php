<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Outlet;
use App\Models\Shift;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardRepository
{
    public function getOutlets(?string $scopeOutletId = null): Collection
    {
        return Outlet::query()
            ->where('is_active', true)
            ->when($scopeOutletId, fn (Builder $query) => $query->whereKey($scopeOutletId))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getOrdersInDateRange(?string $scopeOutletId, CarbonImmutable $startDate, CarbonImmutable $endDate): Collection
    {
        return Order::query()
            ->whereDate('created_at', '>=', $startDate->toDateString())
            ->whereDate('created_at', '<=', $endDate->toDateString())
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->where('status', '!=', 'cancelled')
            ->get([
                'id',
                'outlet_id',
                'source',
                'status',
                'total_amount',
                'discount_amount',
                'paid_amount',
                'metadata',
                'created_at',
            ]);
    }

    public function getTopProductsForOrders(array $orderIds, int $limit = 5): Collection
    {
        if ($orderIds === []) {
            return collect();
        }

        return OrderItem::query()
            ->selectRaw('product_id, SUM(quantity) as total_quantity, SUM(total_price) as total_revenue')
            ->whereIn('order_id', $orderIds)
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->orderByDesc('total_revenue')
            ->with('product:id,name')
            ->limit($limit)
            ->get();
    }

    public function getHourlyRevenueForOrders(Collection $settledOrders): array
    {
        $hourly = array_fill(0, 24, 0.0);
        foreach ($settledOrders as $order) {
            $hour = (int) \Carbon\Carbon::parse($order->created_at)->format('G');
            $hourly[$hour] += (float) $order->total_amount;
        }
        $result = [];
        for ($h = 0; $h < 24; $h++) {
            $result[] = ['hour' => $h, 'revenue' => $hourly[$h]];
        }
        return $result;
    }

    public function getTopProductForOrders(array $orderIds): ?array
    {
        if ($orderIds === []) {
            return null;
        }

        $topItem = OrderItem::query()
            ->selectRaw('product_id, SUM(quantity) as total_quantity, SUM(total_price) as total_revenue')
            ->whereIn('order_id', $orderIds)
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->orderByDesc('total_revenue')
            ->with('product:id,name')
            ->first();

        if (!$topItem) {
            return null;
        }

        return [
            'product_id' => $topItem->product_id,
            'name' => $topItem->product?->name ?? 'Produk tidak ditemukan',
            'quantity' => (int) $topItem->total_quantity,
            'revenue' => (float) $topItem->total_revenue,
        ];
    }

    public function findActiveShift(?string $scopeOutletId): ?Shift
    {
        if (!$scopeOutletId) {
            return null;
        }

        return Shift::query()
            ->with(['user:id,name', 'outlet:id,name'])
            ->where('outlet_id', $scopeOutletId)
            ->where('status', 'active')
            ->latest('opened_at')
            ->first();
    }
}
