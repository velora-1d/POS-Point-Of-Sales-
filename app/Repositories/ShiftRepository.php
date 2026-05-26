<?php

namespace App\Repositories;

use App\Models\EmployeeSchedule;
use App\Models\Order;
use App\Models\Outlet;
use App\Models\Shift;
use App\Models\ShiftCashReport;
use App\Models\ShiftTemplate;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ShiftRepository
{
    public function getOutlets(?string $scopeOutletId = null): Collection
    {
        return Outlet::query()
            ->where('is_active', true)
            ->when($scopeOutletId, fn (Builder $query) => $query->whereKey($scopeOutletId))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getShiftTemplates(?string $scopeOutletId = null): Collection
    {
        return ShiftTemplate::query()
            ->where('is_active', true)
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->orderBy('start_time')
            ->get([
                'id',
                'outlet_id',
                'name',
                'start_time',
                'end_time',
            ]);
    }

    public function getCashierUsers(?string $scopeOutletId = null): Collection
    {
        return User::query()
            ->with('role')
            ->where('is_active', true)
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->whereHas('role', fn (Builder $query) => $query->whereIn('type', ['kasir', 'supervisor']))
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'outlet_id',
                'role_id',
            ])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role?->name,
            ]);
    }

    public function getTodayScheduleForUser(string $userId, CarbonImmutable $date): ?EmployeeSchedule
    {
        return EmployeeSchedule::query()
            ->with('shiftTemplate')
            ->where('user_id', $userId)
            ->whereDate('schedule_date', $date->toDateString())
            ->where('is_active', true)
            ->first();
    }

    public function findActiveShiftForOutlet(string $outletId): ?Shift
    {
        return Shift::query()
            ->with(['user.role', 'user.outlet', 'shiftTemplate', 'opener'])
            ->where('outlet_id', $outletId)
            ->where('status', 'active')
            ->latest('opened_at')
            ->first();
    }

    public function findShiftForScope(string $shiftId, ?string $scopeOutletId = null): ?Shift
    {
        return Shift::query()
            ->with(['user.role', 'user.outlet', 'shiftTemplate', 'opener', 'closer', 'cashReport'])
            ->whereKey($shiftId)
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->first();
    }

    public function findShiftTemplateForOutlet(string $shiftTemplateId, string $outletId): ?ShiftTemplate
    {
        return ShiftTemplate::query()
            ->whereKey($shiftTemplateId)
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->first();
    }

    public function findLastClosedShift(string $outletId): ?Shift
    {
        return Shift::query()
            ->with('cashReport')
            ->where('outlet_id', $outletId)
            ->where('status', 'closed')
            ->latest('closed_at')
            ->first();
    }

    public function paginate(array $filters = [], ?string $scopeOutletId = null): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 10);

        return $this->baseQuery($filters, $scopeOutletId)
            ->orderByDesc('opened_at')
            ->paginate(max(6, min($perPage, 20)))
            ->withQueryString();
    }

    public function getCashRecap(array $filters = [], ?string $scopeOutletId = null): array
    {
        $closedShifts = $this->baseQuery($filters, $scopeOutletId)
            ->where('status', 'closed')
            ->get();

        $differenceShifts = $closedShifts->filter(
            fn (Shift $shift) => abs((float) ($shift->cash_difference ?? 0)) > 0.009,
        );

        return [
            'total_shifts' => $closedShifts->count(),
            'total_orders' => (int) $closedShifts->sum(fn (Shift $shift) => (int) ($shift->cashReport?->total_orders ?? 0)),
            'total_revenue' => (float) $closedShifts->sum(fn (Shift $shift) => (float) ($shift->cashReport?->total_revenue ?? 0)),
            'expected_cash' => (float) $closedShifts->sum(fn (Shift $shift) => (float) ($shift->expected_cash ?? 0)),
            'actual_cash' => (float) $closedShifts->sum(fn (Shift $shift) => (float) ($shift->actual_cash ?? 0)),
            'cash_difference' => (float) $closedShifts->sum(fn (Shift $shift) => (float) ($shift->cash_difference ?? 0)),
            'difference_count' => $differenceShifts->count(),
        ];
    }

    public function getShiftOrders(string $shiftId): Collection
    {
        return Order::query()
            ->where('shift_id', $shiftId)
            ->where('source', 'kasir')
            ->where('status', '!=', 'cancelled')
            ->get([
                'id',
                'status',
                'total_amount',
                'discount_amount',
                'paid_amount',
                'metadata',
            ]);
    }

    public function getCarryOverOrders(string $outletId): Collection
    {
        return Order::query()
            ->where('outlet_id', $outletId)
            ->where('source', 'kasir')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->where(function (Builder $query) {
                $query
                    ->whereNull('shift_id')
                    ->orWhereHas('shift', fn (Builder $shiftQuery) => $shiftQuery->where('status', 'closed'));
            })
            ->get(['id', 'shift_id', 'metadata']);
    }

    public function create(array $payload): Shift
    {
        return Shift::query()->create($payload);
    }

    public function update(Shift $shift, array $payload): Shift
    {
        $shift->update($payload);

        return $shift->fresh(['user.role', 'user.outlet', 'shiftTemplate', 'opener', 'closer', 'cashReport']);
    }

    public function upsertCashReport(Shift $shift, array $payload): ShiftCashReport
    {
        return ShiftCashReport::query()->updateOrCreate(
            ['shift_id' => $shift->id],
            $payload,
        );
    }

    public function assignOrdersToShift(Collection $orders, string $newShiftId): void
    {
        foreach ($orders as $order) {
            $metadata = $order->metadata ?? [];
            $metadata['shift_carry_over'] = [
                'from_shift_id' => $order->shift_id,
                'to_shift_id' => $newShiftId,
                'carried_at' => now()->toIso8601String(),
            ];

            $order->update([
                'shift_id' => $newShiftId,
                'metadata' => $metadata,
            ]);
        }
    }

    public function setOrderShift(string $orderId, string $shiftId): void
    {
        Order::query()
            ->whereKey($orderId)
            ->update(['shift_id' => $shiftId]);
    }

    protected function baseQuery(array $filters = [], ?string $scopeOutletId = null): Builder
    {
        $status = $filters['status'] ?? null;
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $userId = $filters['user_id'] ?? null;

        return Shift::query()
            ->with(['user.role', 'user.outlet', 'shiftTemplate', 'opener', 'closer', 'cashReport'])
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->when($userId, fn (Builder $query) => $query->where('user_id', $userId))
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($startDate, fn (Builder $query) => $query->whereDate('opened_at', '>=', $startDate))
            ->when($endDate, fn (Builder $query) => $query->whereDate('opened_at', '<=', $endDate));
    }
}
