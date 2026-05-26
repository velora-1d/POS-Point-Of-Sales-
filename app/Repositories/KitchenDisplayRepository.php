<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderStatusLog;
use Illuminate\Database\Eloquent\Collection;

class KitchenDisplayRepository
{
    public function getActiveBoardOrders(string $outletId): Collection
    {
        return Order::query()
            ->where('outlet_id', $outletId)
            ->whereIn('status', ['pending', 'in_progress', 'waiting_bar_approval', 'ready'])
            ->with([
                'table:id,name',
                'customer:id,name',
                'items.product:id,name,category_id',
                'items.product.category:id,name',
                'items.variant:id,name',
            ])
            ->orderByRaw("
                CASE status
                    WHEN 'in_progress' THEN 1
                    WHEN 'pending' THEN 2
                    WHEN 'waiting_bar_approval' THEN 3
                    WHEN 'ready' THEN 4
                    ELSE 5
                END
            ")
            ->orderBy('pending_started_at')
            ->orderBy('created_at')
            ->get();
    }

    public function getBarBoardOrders(string $outletId): Collection
    {
        return Order::query()
            ->where('outlet_id', $outletId)
            ->whereIn('status', ['waiting_bar_approval', 'ready'])
            ->with([
                'table:id,name',
                'customer:id,name',
                'items.product:id,name,category_id',
                'items.product.category:id,name',
                'items.variant:id,name',
            ])
            ->orderByRaw("
                CASE status
                    WHEN 'waiting_bar_approval' THEN 1
                    WHEN 'ready' THEN 2
                    ELSE 3
                END
            ")
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->get();
    }

    public function getRecentHistoryLogs(string $outletId, int $limit = 12): Collection
    {
        return OrderStatusLog::query()
            ->whereIn('to_status', ['in_progress', 'waiting_bar_approval', 'ready'])
            ->whereHas('order', function ($query) use ($outletId) {
                $query->where('outlet_id', $outletId);
            })
            ->with([
                'changer:id,name',
                'order.table:id,name',
                'order.customer:id,name',
            ])
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }
}
