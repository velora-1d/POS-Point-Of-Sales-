<?php

namespace App\Repositories;

use App\Models\EmployeeSchedule;
use App\Models\Outlet;
use App\Models\ShiftTemplate;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EmployeeScheduleRepository
{
    public function getEmployees(?string $scopeOutletId = null): Collection
    {
        return User::query()
            ->with(['role', 'outlet'])
            ->where('is_active', true)
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->orderBy('name')
            ->get([
                'id',
                'outlet_id',
                'role_id',
                'name',
                'email',
                'phone',
                'join_date',
                'is_active',
            ]);
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
                'is_active',
            ]);
    }

    public function getOutlets(?string $scopeOutletId = null): Collection
    {
        return Outlet::query()
            ->where('is_active', true)
            ->when($scopeOutletId, fn (Builder $query) => $query->whereKey($scopeOutletId))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getSchedulesForRange(
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?string $scopeOutletId = null,
        ?string $employeeId = null,
    ): Collection {
        return EmployeeSchedule::query()
            ->with(['user.role', 'user.outlet', 'shiftTemplate'])
            ->whereBetween('schedule_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->when($employeeId, fn (Builder $query) => $query->where('user_id', $employeeId))
            ->where('is_active', true)
            ->orderBy('schedule_date')
            ->get();
    }

    public function getTodaySummary(CarbonImmutable $today, ?string $scopeOutletId = null): array
    {
        $baseQuery = EmployeeSchedule::query()
            ->whereDate('schedule_date', $today->toDateString())
            ->where('is_active', true)
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId));

        return [
            'today' => (clone $baseQuery)->count(),
            'morning' => (clone $baseQuery)->whereHas('shiftTemplate', fn (Builder $query) => $query->where('name', 'ilike', '%pagi%'))->count(),
            'evening' => (clone $baseQuery)->whereHas('shiftTemplate', fn (Builder $query) => $query->where('name', 'ilike', '%malam%'))->count(),
            'employees' => (clone $baseQuery)->distinct('user_id')->count('user_id'),
        ];
    }

    public function upsertSchedule(
        string $outletId,
        string $userId,
        string $shiftTemplateId,
        CarbonImmutable $scheduleDate,
    ): void {
        EmployeeSchedule::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'schedule_date' => $scheduleDate->toDateString(),
            ],
            [
                'outlet_id' => $outletId,
                'shift_template_id' => $shiftTemplateId,
                'is_active' => true,
            ],
        );
    }

    public function findEmployeeForOutlet(string $userId, string $outletId): ?User
    {
        return User::query()
            ->whereKey($userId)
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
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
}
