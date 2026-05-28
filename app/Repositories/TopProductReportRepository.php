<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Outlet;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TopProductReportRepository
{
    public function getOutlets(?string $scopeOutletId = null): Collection
    {
        return Outlet::query()
            ->where('is_active', true)
            ->when($scopeOutletId, fn (Builder $query) => $query->whereKey($scopeOutletId))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getCategories(?string $scopeOutletId = null): Collection
    {
        return Category::query()
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'outlet_id']);
    }

    public function getTopProducts(
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?string $scopeOutletId = null,
        ?string $categoryId = null,
    ): Collection {
        return OrderItem::query()
            ->selectRaw('
                order_items.product_id,
                COUNT(DISTINCT order_items.order_id) as total_orders,
                SUM(order_items.quantity) as total_quantity,
                SUM(order_items.total_price) as total_revenue
            ')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('orders.status', '!=', 'cancelled')
            ->whereColumn('orders.paid_amount', '>=', 'orders.total_amount')
            ->whereDate('orders.created_at', '>=', $startDate->toDateString())
            ->whereDate('orders.created_at', '<=', $endDate->toDateString())
            ->when($scopeOutletId, fn (Builder $query) => $query->where('orders.outlet_id', $scopeOutletId))
            ->when($categoryId, fn (Builder $query) => $query->where('products.category_id', $categoryId))
            ->whereNotNull('order_items.product_id')
            ->groupBy('order_items.product_id')
            ->with([
                'product:id,outlet_id,category_id,name,base_price,hpp',
                'product.outlet:id,name',
                'product.category:id,name',
            ])
            ->orderByDesc('total_quantity')
            ->orderByDesc('total_revenue')
            ->get();
    }
}
