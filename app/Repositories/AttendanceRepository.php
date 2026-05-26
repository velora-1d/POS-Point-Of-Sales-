<?php

namespace App\Repositories;

use App\Models\Attendance;
use App\Models\EmployeeSchedule;
use App\Models\Outlet;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AttendanceRepository
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
                'is_active',
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

    public function findScheduleForUserDate(
        string $scheduleId,
        string $userId,
        string $outletId,
        CarbonImmutable $date,
    ): ?EmployeeSchedule {
        return EmployeeSchedule::query()
            ->with('shiftTemplate')
            ->whereKey($scheduleId)
            ->where('user_id', $userId)
            ->where('outlet_id', $outletId)
            ->whereDate('schedule_date', $date->toDateString())
            ->where('is_active', true)
            ->first();
    }

    public function getAttendanceForDate(string $userId, CarbonImmutable $date): ?Attendance
    {
        return Attendance::query()
            ->with(['user.role', 'user.outlet', 'schedule.shiftTemplate', 'correctedBy'])
            ->where('user_id', $userId)
            ->whereDate('date', $date->toDateString())
            ->latest('clock_in')
            ->first();
    }

    public function paginate(
        array $filters = [],
        ?string $scopeOutletId = null,
        ?string $forceUserId = null,
    ): LengthAwarePaginator {
        $perPage = (int) ($filters['per_page'] ?? 12);

        return $this->attendanceQuery($filters, $scopeOutletId, $forceUserId)
            ->orderByDesc('date')
            ->orderByDesc('clock_in')
            ->paginate(max(6, min($perPage, 20)))
            ->withQueryString();
    }

    public function getReportAttendances(
        array $filters = [],
        ?string $scopeOutletId = null,
        ?string $forceUserId = null,
    ): Collection {
        return $this->attendanceQuery($filters, $scopeOutletId, $forceUserId)
            ->orderBy('date')
            ->orderBy('clock_in')
            ->get();
    }

    public function getReportSchedules(
        array $filters = [],
        ?string $scopeOutletId = null,
        ?string $forceUserId = null,
    ): Collection {
        $userId = $filters['user_id'] ?? null;
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        return EmployeeSchedule::query()
            ->with(['user.role', 'user.outlet', 'shiftTemplate'])
            ->where('is_active', true)
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->when($forceUserId, fn (Builder $query) => $query->where('user_id', $forceUserId))
            ->when($userId && !$forceUserId, fn (Builder $query) => $query->where('user_id', $userId))
            ->when($startDate, fn (Builder $query) => $query->whereDate('schedule_date', '>=', $startDate))
            ->when($endDate, fn (Builder $query) => $query->whereDate('schedule_date', '<=', $endDate))
            ->orderBy('schedule_date')
            ->orderBy('user_id')
            ->get();
    }

    public function getTodaySummary(
        CarbonImmutable $date,
        ?string $scopeOutletId = null,
        ?string $forceUserId = null,
    ): array {
        $baseQuery = Attendance::query()
            ->whereDate('date', $date->toDateString())
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->when($forceUserId, fn (Builder $query) => $query->where('user_id', $forceUserId));

        return [
            'today' => (clone $baseQuery)->count(),
            'late' => (clone $baseQuery)->where('status', 'late')->count(),
            'completed' => (clone $baseQuery)->whereNotNull('clock_out')->count(),
            'open' => (clone $baseQuery)->whereNull('clock_out')->count(),
        ];
    }

    public function getRecentForUser(string $userId, int $limit = 7): Collection
    {
        return Attendance::query()
            ->with(['schedule.shiftTemplate', 'correctedBy'])
            ->where('user_id', $userId)
            ->orderByDesc('date')
            ->orderByDesc('clock_in')
            ->limit($limit)
            ->get();
    }

    public function getTodayEntries(CarbonImmutable $date, ?string $scopeOutletId = null): Collection
    {
        return Attendance::query()
            ->with(['user.role', 'user.outlet', 'schedule.shiftTemplate', 'correctedBy'])
            ->whereDate('date', $date->toDateString())
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->orderBy('clock_in')
            ->get();
    }

    public function create(array $payload): Attendance
    {
        return Attendance::query()->create($payload);
    }

    public function update(Attendance $attendance, array $payload): Attendance
    {
        $attendance->update($payload);

        return $attendance->fresh(['user.role', 'user.outlet', 'schedule.shiftTemplate', 'correctedBy']);
    }

    public function findForScope(string $attendanceId, ?string $scopeOutletId = null): ?Attendance
    {
        return Attendance::query()
            ->with(['user.role', 'user.outlet', 'schedule.shiftTemplate', 'correctedBy'])
            ->whereKey($attendanceId)
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->first();
    }

    protected function attendanceQuery(
        array $filters = [],
        ?string $scopeOutletId = null,
        ?string $forceUserId = null,
    ): Builder {
        $status = $filters['status'] ?? null;
        $userId = $filters['user_id'] ?? null;
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        return Attendance::query()
            ->with(['user.role', 'user.outlet', 'schedule.shiftTemplate', 'correctedBy'])
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->when($forceUserId, fn (Builder $query) => $query->where('user_id', $forceUserId))
            ->when($userId && !$forceUserId, fn (Builder $query) => $query->where('user_id', $userId))
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($startDate, fn (Builder $query) => $query->whereDate('date', '>=', $startDate))
            ->when($endDate, fn (Builder $query) => $query->whereDate('date', '<=', $endDate));
    }
}
