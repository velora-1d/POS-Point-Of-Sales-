<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\Outlet;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OutletReportRepository
{
    public function getActiveOutlets(): Collection
    {
        return Outlet::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getSettledOutletMetrics(CarbonImmutable $startDate, CarbonImmutable $endDate): Collection
    {
        return Order::query()
            ->selectRaw('outlet_id, COUNT(*) as total_orders, SUM(total_amount) as total_revenue, SUM(discount_amount) as total_discount')
            ->where('status', '!=', 'cancelled')
            ->whereColumn('paid_amount', '>=', 'total_amount')
            ->whereDate('created_at', '>=', $startDate->toDateString())
            ->whereDate('created_at', '<=', $endDate->toDateString())
            ->whereNotNull('outlet_id')
            ->groupBy('outlet_id')
            ->get()
            ->keyBy('outlet_id');
    }
}
