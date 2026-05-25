<?php

namespace App\Repositories;

use App\Models\Order;
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
                'items.product:id,name',
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
}
