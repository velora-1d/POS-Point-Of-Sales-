<?php

namespace App\Repositories;

use App\Models\NotificationSetting;
use App\Models\Order;
use App\Models\Outlet;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificationSettingRepository
{
    public function getOutlets(): Collection
    {
        return Outlet::query()
            ->with('notificationSetting')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);
    }

    public function findByOutletId(string $outletId): ?NotificationSetting
    {
        return NotificationSetting::query()
            ->where('outlet_id', $outletId)
            ->first();
    }

    public function upsertByOutlet(string $outletId, array $payload): NotificationSetting
    {
        return NotificationSetting::query()->updateOrCreate(
            ['outlet_id' => $outletId],
            $payload,
        );
    }

    public function getOverdueKasbonSnapshot(string $outletId, int $thresholdDays): array
    {
        $referenceDate = CarbonImmutable::today()->subDays(max(1, $thresholdDays));

        $baseQuery = Order::query()
            ->where('outlet_id', $outletId)
            ->whereNotNull('customer_id')
            ->whereColumn('paid_amount', '<', 'total_amount')
            ->where('status', '!=', 'cancelled')
            ->whereDate('created_at', '<=', $referenceDate->toDateString());

        $items = (clone $baseQuery)
            ->with(['customer:id,name,phone,email'])
            ->orderBy('created_at')
            ->limit(5)
            ->get()
            ->map(function (Order $order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer?->name ?: 'Customer',
                    'customer_phone' => $order->customer?->phone,
                    'outstanding_amount' => (float) $order->total_amount - (float) $order->paid_amount,
                    'age_days' => max(0, now()->startOfDay()->diffInDays($order->created_at?->startOfDay() ?? now(), false) * -1),
                    'created_at' => $order->created_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();

        return [
            'count' => (clone $baseQuery)->count(),
            'total_outstanding' => (float) ((clone $baseQuery)->sum(DB::raw('total_amount - paid_amount')) ?? 0),
            'items' => $items,
        ];
    }

    public function getOnlineOrderAlertSnapshot(string $outletId): array
    {
        $today = CarbonImmutable::today();

        $baseQuery = Order::query()
            ->where('outlet_id', $outletId)
            ->whereIn('source', ['gofood', 'grabfood', 'shopeefood', 'maximfood'])
            ->whereDate('created_at', '>=', $today->toDateString());

        $activeStatuses = ['pending', 'in_progress', 'waiting_bar_approval', 'ready'];

        $items = (clone $baseQuery)
            ->with(['customer:id,name,phone'])
            ->whereIn('status', $activeStatuses)
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function (Order $order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'platform' => $order->external_platform ?: $order->source,
                    'status' => $order->status,
                    'customer_name' => $order->customer?->name,
                    'total_amount' => (float) $order->total_amount,
                    'created_at' => $order->created_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();

        return [
            'count' => (clone $baseQuery)->whereIn('status', $activeStatuses)->count(),
            'today_orders' => (clone $baseQuery)->count(),
            'items' => $items,
        ];
    }
}
