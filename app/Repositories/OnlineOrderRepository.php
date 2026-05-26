<?php

namespace App\Repositories;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusLog;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OnlineOrderRepository
{
    public function paginate(array $filters = [], ?string $scopeOutletId = null): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 12);

        return $this->baseQuery($filters, $scopeOutletId)
            ->orderByDesc('created_at')
            ->paginate(max(6, min($perPage, 20)))
            ->withQueryString();
    }

    public function getSummary(array $filters = [], ?string $scopeOutletId = null): array
    {
        $orders = $this->baseQuery($filters, $scopeOutletId)->get();

        return [
            'total_orders' => $orders->count(),
            'total_revenue' => (float) $orders->sum(fn (Order $order) => (float) $order->total_amount),
            'gofood_orders' => $orders->where('external_platform', 'gofood')->count(),
            'grabfood_orders' => $orders->where('external_platform', 'grabfood')->count(),
            'pending_orders' => $orders->whereIn('status', ['pending', 'in_progress', 'waiting_bar_approval', 'ready'])->count(),
        ];
    }

    public function getOutlets(?string $scopeOutletId = null): Collection
    {
        return \App\Models\Outlet::query()
            ->where('is_active', true)
            ->when($scopeOutletId, fn (Builder $query) => $query->whereKey($scopeOutletId))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function findExistingByExternal(string $outletId, string $platform, string $externalOrderId): ?Order
    {
        return Order::query()
            ->where('outlet_id', $outletId)
            ->where('external_platform', $platform)
            ->where('external_order_id', $externalOrderId)
            ->first();
    }

    public function createCustomer(array $payload): Customer
    {
        return Customer::query()->create($payload);
    }

    public function findCustomerByIdentity(string $outletId, ?string $phone, ?string $email): ?Customer
    {
        return Customer::query()
            ->where('outlet_id', $outletId)
            ->where(function (Builder $query) use ($phone, $email) {
                if ($phone) {
                    $query->orWhere('phone', $phone);
                }

                if ($email) {
                    $query->orWhere('email', $email);
                }
            })
            ->first();
    }

    public function createOrder(array $payload): Order
    {
        return Order::query()->create($payload);
    }

    public function createOrderItem(Order $order, array $payload): OrderItem
    {
        return $order->items()->create($payload);
    }

    public function createStatusLog(array $payload): OrderStatusLog
    {
        return OrderStatusLog::query()->create($payload);
    }

    public function getRecentHistoryLogs(array $filters = [], ?string $scopeOutletId = null, int $limit = 15): Collection
    {
        $platform = $filters['platform'] ?? null;
        $status = $filters['status'] ?? null;
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        return OrderStatusLog::query()
            ->whereHas('order', function (Builder $query) use (
                $scopeOutletId,
                $platform,
                $status,
                $startDate,
                $endDate
            ) {
                $query->whereIn('source', ['gofood', 'grabfood'])
                    ->when($scopeOutletId, fn (Builder $builder) => $builder->where('outlet_id', $scopeOutletId))
                    ->when($platform, fn (Builder $builder) => $builder->where('external_platform', $platform))
                    ->when($status, fn (Builder $builder) => $builder->where('status', $status))
                    ->when($startDate, fn (Builder $builder) => $builder->whereDate('created_at', '>=', $startDate))
                    ->when($endDate, fn (Builder $builder) => $builder->whereDate('created_at', '<=', $endDate));
            })
            ->with([
                'changer:id,name',
                'order:id,order_number,source,status,external_platform,external_order_id,outlet_id,customer_id,metadata',
                'order.customer:id,name',
                'order.outlet:id,name',
            ])
            ->latest('created_at')
            ->limit(max(6, min($limit, 30)))
            ->get();
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

    protected function baseQuery(array $filters = [], ?string $scopeOutletId = null): Builder
    {
        $platform = $filters['platform'] ?? null;
        $status = $filters['status'] ?? null;
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        return Order::query()
            ->with(['customer', 'items.product', 'items.variant', 'cashier', 'outlet'])
            ->whereIn('source', ['gofood', 'grabfood'])
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->when($platform, fn (Builder $query) => $query->where('external_platform', $platform))
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($startDate, fn (Builder $query) => $query->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn (Builder $query) => $query->whereDate('created_at', '<=', $endDate));
    }
}
