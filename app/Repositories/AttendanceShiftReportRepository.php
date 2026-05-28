<?php

namespace App\Repositories;

use App\Models\Attendance;
use App\Models\EmployeeSchedule;
use App\Models\Outlet;
use App\Models\Shift;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AttendanceShiftReportRepository
{
    public function getOutlets(?string $scopeOutletId = null): Collection
    {
        return Outlet::query()
            ->where('is_active', true)
            ->when($scopeOutletId, fn (Builder $query) => $query->whereKey($scopeOutletId))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getEmployees(?string $scopeOutletId = null): Collection
    {
        return User::query()
            ->with('role')
            ->where('is_active', true)
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->whereHas('role', fn (Builder $query) => $query->whereIn('type', ['supervisor', 'kasir', 'bar', 'kitchen']))
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

    public function getAttendances(
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?string $scopeOutletId = null,
        ?string $userId = null,
    ): Collection {
        return Attendance::query()
            ->with(['user.outlet', 'schedule.shiftTemplate'])
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->when($userId, fn (Builder $query) => $query->where('user_id', $userId))
            ->whereDate('date', '>=', $startDate->toDateString())
            ->whereDate('date', '<=', $endDate->toDateString())
            ->orderBy('date')
            ->orderBy('clock_in')
            ->get();
    }

    public function getSchedules(
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?string $scopeOutletId = null,
        ?string $userId = null,
    ): Collection {
        return EmployeeSchedule::query()
            ->with(['user.outlet', 'shiftTemplate'])
            ->where('is_active', true)
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->when($userId, fn (Builder $query) => $query->where('user_id', $userId))
            ->whereDate('schedule_date', '>=', $startDate->toDateString())
            ->whereDate('schedule_date', '<=', $endDate->toDateString())
            ->orderBy('schedule_date')
            ->orderBy('user_id')
            ->get();
    }

    public function getShifts(
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?string $scopeOutletId = null,
        ?string $userId = null,
    ): Collection {
        return Shift::query()
            ->with(['user.outlet', 'shiftTemplate', 'cashReport'])
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->when($userId, fn (Builder $query) => $query->where('user_id', $userId))
            ->whereDate('opened_at', '>=', $startDate->toDateString())
            ->whereDate('opened_at', '<=', $endDate->toDateString())
            ->orderBy('opened_at')
            ->get();
    }
}
