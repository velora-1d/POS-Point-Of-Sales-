<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\EmployeeSchedule;
use App\Models\User;
use App\Repositories\AttendanceRepository;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function __construct(
        protected AttendanceRepository $attendanceRepository,
    ) {
    }

    public function getDashboard(User $actor, array $filters = []): array
    {
        $this->assertCanAccess($actor);

        $canManage = $this->canManage($actor);
        $scopeOutletId = $canManage
            ? ($actor->role?->type === 'owner' ? ($filters['outlet_id'] ?? null) : $actor->outlet_id)
            : $actor->outlet_id;
        $resolvedFilters = [
            'status' => $filters['status'] ?? '',
            'user_id' => $canManage ? ($filters['user_id'] ?? '') : $actor->id,
            'outlet_id' => $scopeOutletId ?? '',
            'start_date' => $filters['start_date'] ?? CarbonImmutable::today()->startOfMonth()->toDateString(),
            'end_date' => $filters['end_date'] ?? CarbonImmutable::today()->toDateString(),
            'per_page' => (int) ($filters['per_page'] ?? 12),
        ];
        $today = CarbonImmutable::today();

        return [
            'summary' => $this->attendanceRepository->getTodaySummary(
                $today,
                $scopeOutletId,
                $canManage ? null : $actor->id,
            ),
            'attendanceReport' => $canManage
                ? $this->buildAttendanceReport(
                    array_merge($resolvedFilters, ['status' => '']),
                    $scopeOutletId,
                )
                : null,
            'selfAttendance' => $this->transformAttendance(
                $this->attendanceRepository->getAttendanceForDate($actor->id, $today),
            ),
            'todaySchedule' => $this->transformSchedule(
                $this->attendanceRepository->getTodayScheduleForUser($actor->id, $today),
            ),
            'recentAttendances' => $this->transformAttendanceCollection(
                $this->attendanceRepository->getRecentForUser($actor->id),
            ),
            'todayEntries' => $canManage
                ? $this->transformAttendanceCollection(
                    $this->attendanceRepository->getTodayEntries($today, $scopeOutletId),
                )
                : collect(),
            'attendances' => $this->transformAttendancePaginator(
                $this->attendanceRepository->paginate(
                    $resolvedFilters,
                    $scopeOutletId,
                    $canManage ? null : $actor->id,
                ),
            ),
            'filters' => $resolvedFilters,
            'referenceData' => [
                'outlets' => $this->attendanceRepository->getOutlets(
                    $actor->role?->type === 'owner' ? null : $actor->outlet_id,
                ),
                'employees' => $this->attendanceRepository->getEmployees($scopeOutletId),
            ],
            'canManage' => $canManage,
            'canClock' => true,
        ];
    }

    public function clockIn(array $payload, User $actor): void
    {
        $this->assertCanAccess($actor);

        if (!$actor->outlet_id) {
            throw ValidationException::withMessages([
                'attendance' => 'User belum terhubung ke outlet aktif.',
            ]);
        }

        $now = CarbonImmutable::now();
        $today = $now->startOfDay();
        $existingAttendance = $this->attendanceRepository->getAttendanceForDate($actor->id, $today);

        if ($existingAttendance?->clock_out) {
            throw ValidationException::withMessages([
                'attendance' => 'Absensi hari ini sudah lengkap. Clock in tidak bisa diulang.',
            ]);
        }

        if ($existingAttendance?->clock_in) {
            throw ValidationException::withMessages([
                'attendance' => 'Anda sudah clock in hari ini.',
            ]);
        }

        $schedule = $this->resolveClockSchedule($payload, $actor, $today);
        $status = $this->resolveAttendanceStatus($schedule, $now);

        DB::transaction(function () use ($actor, $payload, $today, $now, $schedule, $status) {
            $this->attendanceRepository->create([
                'outlet_id' => $actor->outlet_id,
                'user_id' => $actor->id,
                'schedule_id' => $schedule?->id,
                'clock_in' => $now,
                'clock_out' => null,
                'status' => $status,
                'notes' => filled($payload['notes'] ?? null) ? trim((string) $payload['notes']) : null,
                'date' => $today,
            ]);
        });
    }

    public function clockOut(array $payload, User $actor): void
    {
        $this->assertCanAccess($actor);

        $today = CarbonImmutable::today();
        $attendance = $this->attendanceRepository->getAttendanceForDate($actor->id, $today);

        if (!$attendance?->clock_in) {
            throw ValidationException::withMessages([
                'attendance' => 'Belum ada clock in hari ini.',
            ]);
        }

        if ($attendance->clock_out) {
            throw ValidationException::withMessages([
                'attendance' => 'Clock out hari ini sudah tercatat.',
            ]);
        }

        DB::transaction(function () use ($attendance, $payload) {
            $this->attendanceRepository->update($attendance, [
                'clock_out' => CarbonImmutable::now(),
                'notes' => $this->mergeNotes($attendance->notes, $payload['notes'] ?? null),
            ]);
        });
    }

    public function correct(Attendance $attendance, array $payload, User $actor): void
    {
        $scopeOutletId = $this->resolveManagedOutletId($actor);
        $scopedAttendance = $this->attendanceRepository->findForScope($attendance->id, $scopeOutletId);

        if (!$scopedAttendance) {
            abort(404);
        }

        $clockIn = CarbonImmutable::parse($payload['clock_in']);
        $clockOut = filled($payload['clock_out'] ?? null)
            ? CarbonImmutable::parse($payload['clock_out'])
            : null;

        if ($clockOut && $clockOut->lessThan($clockIn)) {
            throw ValidationException::withMessages([
                'clock_out' => 'Clock out tidak boleh lebih awal dari clock in.',
            ]);
        }

        $attendanceDate = CarbonImmutable::parse($scopedAttendance->date)->startOfDay();
        $schedule = $scopedAttendance->schedule
            ?: $this->attendanceRepository->getTodayScheduleForUser($scopedAttendance->user_id, $attendanceDate);
        $status = $this->resolveAttendanceStatus($schedule, $clockIn);

        DB::transaction(function () use ($scopedAttendance, $payload, $actor, $clockIn, $clockOut, $status) {
            $this->attendanceRepository->update($scopedAttendance, [
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'status' => $status,
                'notes' => filled($payload['notes'] ?? null) ? trim((string) $payload['notes']) : null,
                'corrected_by' => $actor->id,
                'corrected_at' => CarbonImmutable::now(),
                'correction_reason' => trim((string) $payload['correction_reason']),
            ]);
        });
    }

    protected function resolveClockSchedule(array $payload, User $actor, CarbonImmutable $today): ?EmployeeSchedule
    {
        if (filled($payload['schedule_id'] ?? null)) {
            $schedule = $this->attendanceRepository->findScheduleForUserDate(
                (string) $payload['schedule_id'],
                $actor->id,
                $actor->outlet_id,
                $today,
            );

            if (!$schedule) {
                throw ValidationException::withMessages([
                    'schedule_id' => 'Jadwal shift tidak valid untuk hari ini.',
                ]);
            }

            return $schedule;
        }

        return $this->attendanceRepository->getTodayScheduleForUser($actor->id, $today);
    }

    protected function resolveAttendanceStatus(?EmployeeSchedule $schedule, CarbonImmutable $clockIn): string
    {
        if (!$schedule?->shiftTemplate?->start_time) {
            return 'present';
        }

        $scheduleStart = CarbonImmutable::parse(
            $schedule->schedule_date->format('Y-m-d') . ' ' . $schedule->shiftTemplate->start_time,
        );

        return $clockIn->greaterThan($scheduleStart) ? 'late' : 'present';
    }

    protected function transformAttendancePaginator(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        $paginator->setCollection(
            $this->transformAttendanceCollection($paginator->getCollection()),
        );

        return $paginator;
    }

    protected function transformAttendanceCollection(Collection $attendances): Collection
    {
        return $attendances->map(fn ($attendance) => $this->transformAttendance($attendance));
    }

    protected function buildAttendanceReport(array $filters, ?string $scopeOutletId = null): array
    {
        $attendances = $this->transformAttendanceCollection(
            $this->attendanceRepository->getReportAttendances($filters, $scopeOutletId),
        );
        $schedules = $this->attendanceRepository->getReportSchedules($filters, $scopeOutletId);
        $attendanceMap = $attendances->keyBy(function (Attendance $attendance) {
            return $attendance->user_id.'|'.$attendance->date?->toDateString();
        });
        $absences = $schedules
            ->filter(function (EmployeeSchedule $schedule) use ($attendanceMap) {
                return !$attendanceMap->has($schedule->user_id.'|'.$schedule->schedule_date?->toDateString());
            })
            ->map(function (EmployeeSchedule $schedule) {
                return [
                    'schedule_id' => $schedule->id,
                    'date' => $schedule->schedule_date?->toDateString(),
                    'user' => $schedule->user ? [
                        'id' => $schedule->user->id,
                        'name' => $schedule->user->name,
                        'role' => $schedule->user->role ? [
                            'id' => $schedule->user->role->id,
                            'name' => $schedule->user->role->name,
                            'type' => $schedule->user->role->type,
                        ] : null,
                        'outlet' => $schedule->user->outlet ? [
                            'id' => $schedule->user->outlet->id,
                            'name' => $schedule->user->outlet->name,
                        ] : null,
                    ] : null,
                    'shift_template_name' => $schedule->shiftTemplate?->name,
                    'shift_time' => $schedule->shiftTemplate
                        ? substr((string) $schedule->shiftTemplate->start_time, 0, 5).' - '.substr((string) $schedule->shiftTemplate->end_time, 0, 5)
                        : 'Tanpa template',
                ];
            })
            ->values();

        $presentCount = $attendances->where('status', 'present')->count();
        $lateCount = $attendances->where('status', 'late')->count();
        $leaveCount = $attendances->where('status', 'leave')->count();
        $absentCount = $absences->count();
        $scheduledDays = $schedules->count();
        $attendanceRate = $scheduledDays > 0
            ? (($presentCount + $lateCount) / $scheduledDays) * 100
            : 0;

        return [
            'present' => $presentCount,
            'late' => $lateCount,
            'absent' => $absentCount,
            'leave' => $leaveCount,
            'scheduled_days' => $scheduledDays,
            'recorded_days' => $attendances->count(),
            'employee_count' => $schedules->pluck('user_id')->filter()->unique()->count(),
            'attendance_rate' => round($attendanceRate, 2),
            'absences' => $absences,
        ];
    }

    protected function transformAttendance(?Attendance $attendance): ?Attendance
    {
        if (!$attendance) {
            return null;
        }

        $attendance->setAttribute(
            'work_duration_label',
            $this->formatDuration($attendance->clock_in, $attendance->clock_out),
        );
        $attendance->setAttribute(
            'schedule_summary',
            $attendance->schedule?->shiftTemplate
                ? $attendance->schedule->shiftTemplate->name . ' • ' .
                    substr((string) $attendance->schedule->shiftTemplate->start_time, 0, 5) . ' - ' .
                    substr((string) $attendance->schedule->shiftTemplate->end_time, 0, 5)
                : 'Tanpa jadwal shift',
        );
        $attendance->setAttribute('corrected_by_name', $attendance->correctedBy?->name);

        return $attendance;
    }

    protected function transformSchedule(?EmployeeSchedule $schedule): ?array
    {
        if (!$schedule) {
            return null;
        }

        return [
            'id' => $schedule->id,
            'schedule_date' => $schedule->schedule_date?->toDateString(),
            'shift_template_id' => $schedule->shift_template_id,
            'shift_template_name' => $schedule->shiftTemplate?->name,
            'start_time' => $schedule->shiftTemplate?->start_time,
            'end_time' => $schedule->shiftTemplate?->end_time,
        ];
    }

    protected function formatDuration(?\Carbon\CarbonInterface $clockIn, ?\Carbon\CarbonInterface $clockOut): string
    {
        if (!$clockIn) {
            return '-';
        }

        if (!$clockOut) {
            return 'Masih berjalan';
        }

        $minutes = $clockOut->diffInMinutes($clockIn);

        return sprintf('%d jam %02d menit', intdiv($minutes, 60), $minutes % 60);
    }

    protected function mergeNotes(?string $existingNotes, ?string $newNotes): ?string
    {
        $notes = collect([$existingNotes, filled($newNotes) ? trim((string) $newNotes) : null])
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => trim((string) $value))
            ->unique()
            ->values();

        return $notes->isEmpty() ? null : $notes->implode("\n");
    }

    public function canManage(User $actor): bool
    {
        return in_array($actor->role?->type, ['owner', 'supervisor'], true);
    }

    protected function assertCanAccess(User $actor): void
    {
        if (!$actor->is_active) {
            abort(403, 'User nonaktif tidak bisa mengakses absensi.');
        }
    }

    protected function resolveManagedOutletId(User $actor): ?string
    {
        if ($actor->role?->type === 'owner') {
            return null;
        }

        if ($actor->role?->type === 'supervisor') {
            return $actor->outlet_id;
        }

        abort(403, 'Hanya supervisor atau owner yang bisa mengoreksi absensi.');
    }
}
