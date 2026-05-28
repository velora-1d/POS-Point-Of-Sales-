<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\Outlet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SalesReportRepository
{
    public function getOutlets(?string $scopeOutletId = null): Collection
    {
        return Outlet::query()
            ->where('is_active', true)
            ->when($scopeOutletId, fn (Builder $query) => $query->whereKey($scopeOutletId))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getSettledOrdersSnapshot(array $filters = [], ?string $scopeOutletId = null): Collection
    {
        return $this->baseSettledOrdersQuery($filters, $scopeOutletId)
            ->with(['outlet:id,name', 'cashier:id,name'])
            ->withSum('items as total_items_quantity', 'quantity')
            ->orderBy('created_at')
            ->get();
    }

    public function paginateSettledOrders(array $filters = [], ?string $scopeOutletId = null): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 10);

        return $this->baseSettledOrdersQuery($filters, $scopeOutletId)
            ->with(['outlet:id,name', 'cashier:id,name'])
            ->withSum('items as total_items_quantity', 'quantity')
            ->orderByDesc('created_at')
            ->paginate(max(5, min($perPage, 25)))
            ->withQueryString();
    }

    protected function baseSettledOrdersQuery(array $filters = [], ?string $scopeOutletId = null): Builder
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $source = $filters['source'] ?? null;
        $paymentMethod = $filters['payment_method'] ?? null;
        $search = trim((string) ($filters['search'] ?? ''));

        return Order::query()
            ->select([
                'id',
                'outlet_id',
                'cashier_id',
                'order_number',
                'source',
                'type',
                'status',
                'subtotal',
                'discount_amount',
                'total_amount',
                'paid_amount',
                'external_order_id',
                'metadata',
                'created_at',
            ])
            ->where('status', '!=', 'cancelled')
            ->whereColumn('paid_amount', '>=', 'total_amount')
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->when($startDate, fn (Builder $query) => $query->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn (Builder $query) => $query->whereDate('created_at', '<=', $endDate))
            ->when($source, fn (Builder $query) => $query->where('source', $source))
            ->when($paymentMethod, fn (Builder $query) => $query->where('metadata->payment->method', $paymentMethod))
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $searchQuery) use ($search) {
                    $searchQuery
                        ->where('order_number', 'like', '%' . $search . '%')
                        ->orWhere('external_order_id', 'like', '%' . $search . '%');
                });
            });
    }
}
