<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\EmployeeSchedule;
use App\Models\Shift;
use App\Models\User;
use App\Repositories\AttendanceShiftReportRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class AttendanceShiftReportService
{
    public function __construct(
        protected AttendanceShiftReportRepository $attendanceShiftReportRepository,
    ) {
    }

    public function getReport(User $actor, array $filters = []): array
    {
        $this->assertCanRead($actor);

        $scopeOutletId = $this->resolveScopeOutletId($actor, $filters['outlet_id'] ?? null);
        $resolvedFilters = $this->resolveFilters($filters, $scopeOutletId);
        $startDate = CarbonImmutable::parse($resolvedFilters['start_date']);
        $endDate = CarbonImmutable::parse($resolvedFilters['end_date']);

        $attendances = $this->attendanceShiftReportRepository->getAttendances(
            $startDate,
            $endDate,
            $scopeOutletId,
            $resolvedFilters['user_id'] ?: null,
        );
        $schedules = $this->attendanceShiftReportRepository->getSchedules(
            $startDate,
            $endDate,
            $scopeOutletId,
            $resolvedFilters['user_id'] ?: null,
        );
        $shifts = $this->attendanceShiftReportRepository->getShifts(
            $startDate,
            $endDate,
            $scopeOutletId,
            $resolvedFilters['user_id'] ?: null,
        );

        $employeeRows = $this->buildEmployeeRows($attendances, $schedules, $shifts);
        $missingAttendances = $this->buildMissingAttendanceRows($attendances, $schedules);
        $shiftAnomalies = $this->buildShiftAnomalyRows($shifts);

        return [
            'summary' => $this->buildSummary($employeeRows, $shifts),
            'employees' => $employeeRows->all(),
            'missingAttendances' => $missingAttendances->take(12)->values()->all(),
            'shiftAnomalies' => $shiftAnomalies->take(12)->values()->all(),
            'filters' => $resolvedFilters,
            'referenceData' => [
                'outlets' => $this->attendanceShiftReportRepository->getOutlets(
                    $actor->role?->type === 'owner' ? null : $scopeOutletId,
                ),
                'employees' => $this->attendanceShiftReportRepository->getEmployees($scopeOutletId),
            ],
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'limitations' => [
                'attendance' => 'Persentase kehadiran dihitung dari jadwal aktif vs absensi tercatat pada periode terpilih. Hari tanpa jadwal tidak dihitung sebagai absen.',
                'shift' => 'Revenue dan recap kas hanya memakai shift berstatus closed yang sudah punya cash report. Shift aktif tetap tampil sebagai count operasional.',
            ],
        ];
    }

    protected function buildEmployeeRows(
        Collection $attendances,
        Collection $schedules,
        Collection $shifts,
    ): Collection {
        $attendanceGroups = $attendances->groupBy('user_id');
        $scheduleGroups = $schedules->groupBy('user_id');
        $shiftGroups = $shifts->groupBy('user_id');
        $userIds = $attendanceGroups->keys()
            ->merge($scheduleGroups->keys())
            ->merge($shiftGroups->keys())
            ->unique()
            ->values();

        return $userIds->map(function (string $userId) use ($attendanceGroups, $scheduleGroups, $shiftGroups) {
            /** @var Collection<int, Attendance> $attendanceGroup */
            $attendanceGroup = $attendanceGroups->get($userId, collect());
            /** @var Collection<int, EmployeeSchedule> $scheduleGroup */
            $scheduleGroup = $scheduleGroups->get($userId, collect());
            /** @var Collection<int, Shift> $shiftGroup */
            $shiftGroup = $shiftGroups->get($userId, collect());

            $attendanceDateKeys = $attendanceGroup
                ->map(fn (Attendance $attendance) => $attendance->date?->toDateString())
                ->filter()
                ->unique()
                ->values();
            $scheduleDateKeys = $scheduleGroup
                ->map(fn (EmployeeSchedule $schedule) => $schedule->schedule_date?->toDateString())
                ->filter()
                ->unique()
                ->values();
            $missingSchedules = $scheduleGroup->filter(
                fn (EmployeeSchedule $schedule) => !$attendanceDateKeys->contains($schedule->schedule_date?->toDateString()),
            );
            $matchedScheduleCount = $scheduleGroup->count() - $missingSchedules->count();
            $closedShifts = $shiftGroup->where('status', 'closed');
            $referenceUser = $scheduleGroup->first()?->user
                ?? $attendanceGroup->first()?->user
                ?? $shiftGroup->first()?->user;

            return [
                'id' => $userId,
                'name' => $referenceUser?->name ?? 'Karyawan tidak ditemukan',
                'outlet' => $referenceUser?->outlet ? [
                    'id' => $referenceUser->outlet->id,
                    'name' => $referenceUser->outlet->name,
                ] : null,
                'attendance' => [
                    'scheduled_days' => $scheduleGroup->count(),
                    'attendance_days' => $attendanceDateKeys->count(),
                    'matched_schedules' => $matchedScheduleCount,
                    'late_days' => $attendanceGroup->where('status', 'late')->count(),
                    'completed_days' => $attendanceGroup->filter(
                        fn (Attendance $attendance) => $attendance->clock_out !== null,
                    )->count(),
                    'missing_days' => $missingSchedules->count(),
                    'unscheduled_days' => $attendanceGroup->filter(
                        fn (Attendance $attendance) => !$scheduleDateKeys->contains($attendance->date?->toDateString()),
                    )->count(),
                    'attendance_rate' => $scheduleGroup->count() > 0
                        ? ($matchedScheduleCount / $scheduleGroup->count()) * 100
                        : null,
                    'missing_dates' => $missingSchedules
                        ->take(3)
                        ->map(fn (EmployeeSchedule $schedule) => [
                            'date' => $schedule->schedule_date?->toDateString(),
                            'shift_template_name' => $schedule->shiftTemplate?->name,
                        ])
                        ->values()
                        ->all(),
                ],
                'shift' => [
                    'total_shifts' => $shiftGroup->count(),
                    'active_shifts' => $shiftGroup->where('status', 'active')->count(),
                    'closed_shifts' => $closedShifts->count(),
                    'total_orders' => (int) $closedShifts->sum(
                        fn (Shift $shift) => (int) ($shift->cashReport?->total_orders ?? 0),
                    ),
                    'total_revenue' => (float) $closedShifts->sum(
                        fn (Shift $shift) => (float) ($shift->cashReport?->total_revenue ?? 0),
                    ),
                    'expected_cash' => (float) $closedShifts->sum(
                        fn (Shift $shift) => (float) ($shift->expected_cash ?? 0),
                    ),
                    'actual_cash' => (float) $closedShifts->sum(
                        fn (Shift $shift) => (float) ($shift->actual_cash ?? 0),
                    ),
                    'cash_difference' => (float) $closedShifts->sum(
                        fn (Shift $shift) => (float) ($shift->cash_difference ?? 0),
                    ),
                    'difference_count' => $closedShifts->filter(
                        fn (Shift $shift) => abs((float) ($shift->cash_difference ?? 0)) > 0.009,
                    )->count(),
                ],
            ];
        })
            ->sortByDesc(fn (array $row) => (
                ($row['attendance']['missing_days'] * 1000000)
                + ($row['shift']['difference_count'] * 10000)
                + ($row['attendance']['late_days'] * 100)
                + $row['shift']['closed_shifts']
            ))
            ->values();
    }

    protected function buildMissingAttendanceRows(Collection $attendances, Collection $schedules): Collection
    {
        $attendanceMap = $attendances->mapWithKeys(function (Attendance $attendance) {
            return [$attendance->user_id.'|'.$attendance->date?->toDateString() => true];
        });

        return $schedules
            ->filter(function (EmployeeSchedule $schedule) use ($attendanceMap) {
                return !$attendanceMap->has($schedule->user_id.'|'.$schedule->schedule_date?->toDateString());
            })
            ->map(function (EmployeeSchedule $schedule) {
                return [
                    'id' => $schedule->id,
                    'date' => $schedule->schedule_date?->toDateString(),
                    'employee_name' => $schedule->user?->name ?? 'Karyawan tidak ditemukan',
                    'outlet_name' => $schedule->user?->outlet?->name ?? '-',
                    'shift_template_name' => $schedule->shiftTemplate?->name ?? 'Tanpa template',
                    'start_time' => $schedule->shiftTemplate?->start_time,
                    'end_time' => $schedule->shiftTemplate?->end_time,
                ];
            })
            ->sortByDesc('date')
            ->values();
    }

    protected function buildShiftAnomalyRows(Collection $shifts): Collection
    {
        return $shifts
            ->where('status', 'closed')
            ->filter(fn (Shift $shift) => abs((float) ($shift->cash_difference ?? 0)) > 0.009)
            ->map(function (Shift $shift) {
                return [
                    'id' => $shift->id,
                    'opened_at' => $shift->opened_at?->toIso8601String(),
                    'employee_name' => $shift->user?->name ?? 'Kasir tidak ditemukan',
                    'outlet_name' => $shift->user?->outlet?->name ?? '-',
                    'shift_template_name' => $shift->shiftTemplate?->name ?? 'Tanpa template',
                    'total_revenue' => (float) ($shift->cashReport?->total_revenue ?? 0),
                    'expected_cash' => (float) ($shift->expected_cash ?? 0),
                    'actual_cash' => (float) ($shift->actual_cash ?? 0),
                    'cash_difference' => (float) ($shift->cash_difference ?? 0),
                ];
            })
            ->sortByDesc(fn (array $row) => abs($row['cash_difference']))
            ->values();
    }

    protected function buildSummary(Collection $employeeRows, Collection $shifts): array
    {
        $closedShifts = $shifts->where('status', 'closed');
        $totalSchedules = (int) $employeeRows->sum(fn (array $row) => $row['attendance']['scheduled_days']);
        $matchedSchedules = (int) $employeeRows->sum(fn (array $row) => $row['attendance']['matched_schedules']);

        return [
            'employees_monitored' => $employeeRows->count(),
            'total_schedules' => $totalSchedules,
            'total_attendances' => (int) $employeeRows->sum(fn (array $row) => $row['attendance']['attendance_days']),
            'late_count' => (int) $employeeRows->sum(fn (array $row) => $row['attendance']['late_days']),
            'completed_count' => (int) $employeeRows->sum(fn (array $row) => $row['attendance']['completed_days']),
            'missing_attendances' => (int) $employeeRows->sum(fn (array $row) => $row['attendance']['missing_days']),
            'unscheduled_attendances' => (int) $employeeRows->sum(fn (array $row) => $row['attendance']['unscheduled_days']),
            'attendance_rate' => $totalSchedules > 0
                ? ($matchedSchedules / $totalSchedules) * 100
                : null,
            'total_shifts' => $shifts->count(),
            'active_shifts' => $shifts->where('status', 'active')->count(),
            'closed_shifts' => $closedShifts->count(),
            'total_shift_orders' => (int) $closedShifts->sum(
                fn (Shift $shift) => (int) ($shift->cashReport?->total_orders ?? 0),
            ),
            'total_shift_revenue' => (float) $closedShifts->sum(
                fn (Shift $shift) => (float) ($shift->cashReport?->total_revenue ?? 0),
            ),
            'expected_cash' => (float) $closedShifts->sum(
                fn (Shift $shift) => (float) ($shift->expected_cash ?? 0),
            ),
            'actual_cash' => (float) $closedShifts->sum(
                fn (Shift $shift) => (float) ($shift->actual_cash ?? 0),
            ),
            'cash_difference' => (float) $closedShifts->sum(
                fn (Shift $shift) => (float) ($shift->cash_difference ?? 0),
            ),
            'difference_count' => $closedShifts->filter(
                fn (Shift $shift) => abs((float) ($shift->cash_difference ?? 0)) > 0.009,
            )->count(),
        ];
    }

    protected function resolveFilters(array $filters, ?string $scopeOutletId): array
    {
        return [
            'start_date' => $filters['start_date'] ?? CarbonImmutable::today()->startOfMonth()->toDateString(),
            'end_date' => $filters['end_date'] ?? CarbonImmutable::today()->toDateString(),
            'outlet_id' => $scopeOutletId ?? '',
            'user_id' => $filters['user_id'] ?? '',
        ];
    }

    protected function resolveScopeOutletId(User $actor, ?string $requestedOutletId): ?string
    {
        if ($actor->role?->type === 'owner') {
            return $requestedOutletId ?: null;
        }

        if (!$actor->outlet_id) {
            abort(403, 'User belum terhubung ke outlet aktif.');
        }

        return $actor->outlet_id;
    }

    protected function assertCanRead(User $actor): void
    {
        if (!in_array($actor->role?->type, ['owner', 'supervisor'], true)) {
            abort(403, 'Laporan absensi dan shift hanya tersedia untuk owner dan supervisor.');
        }
    }
}
