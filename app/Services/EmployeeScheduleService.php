<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\EmployeeScheduleRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeScheduleService
{
    public function __construct(
        protected EmployeeScheduleRepository $employeeScheduleRepository,
    ) {
    }

    public function getDashboard(User $actor, array $filters = []): array
    {
        $this->assertCanRead($actor);

        $scopeOutletId = $actor->role?->type === 'owner'
            ? ($filters['outlet_id'] ?? null)
            : $actor->outlet_id;
        $resolvedFilters = $filters;

        if ($scopeOutletId) {
            $resolvedFilters['outlet_id'] = $scopeOutletId;
        }

        $weekStart = $this->resolveWeekStart($filters['week_start'] ?? null);
        $weekEnd = $weekStart->addDays(6);

        return [
            'summary' => $this->employeeScheduleRepository->getTodaySummary(CarbonImmutable::today(), $scopeOutletId),
            'employees' => $this->employeeScheduleRepository->getEmployees($scopeOutletId),
            'shiftTemplates' => $this->employeeScheduleRepository->getShiftTemplates($scopeOutletId),
            'todaySchedules' => $this->employeeScheduleRepository->getSchedulesForRange(
                CarbonImmutable::today(),
                CarbonImmutable::today(),
                $scopeOutletId,
            ),
            'schedules' => $this->employeeScheduleRepository->getSchedulesForRange(
                $weekStart,
                $weekEnd,
                $scopeOutletId,
                $resolvedFilters['employee_id'] ?? null,
            ),
            'filters' => [
                'week_start' => $weekStart->toDateString(),
                'employee_id' => $resolvedFilters['employee_id'] ?? '',
                'outlet_id' => $resolvedFilters['outlet_id'] ?? '',
            ],
            'days' => collect(range(0, 6))->map(fn (int $offset) => $weekStart->addDays($offset)->toDateString()),
            'referenceData' => [
                'outlets' => $this->employeeScheduleRepository->getOutlets(
                    $actor->role?->type === 'owner' ? null : $scopeOutletId,
                ),
            ],
            'canManage' => in_array($actor->role?->type, ['owner', 'supervisor'], true),
        ];
    }

    public function assignDaily(array $payload, User $actor): void
    {
        $this->assertCanManage($actor);
        $outletId = $this->resolveManagedOutletId($actor, $payload['outlet_id']);
        $this->validateAssignmentReferences($outletId, $payload['user_id'], [$payload['shift_template_id']]);
        $scheduleDate = CarbonImmutable::parse($payload['schedule_date']);

        DB::transaction(function () use ($payload, $scheduleDate, $outletId) {
            if (filled($payload['takeover_from_user_id'] ?? null)) {
                \App\Models\EmployeeSchedule::query()
                    ->where('user_id', $payload['takeover_from_user_id'])
                    ->whereDate('schedule_date', $scheduleDate->toDateString())
                    ->delete();
            }

            $this->employeeScheduleRepository->upsertSchedule(
                $outletId,
                $payload['user_id'],
                $payload['shift_template_id'],
                $scheduleDate,
            );
        });
    }

    public function assignWeekly(array $payload, User $actor): void
    {
        $this->assertCanManage($actor);
        $outletId = $this->resolveManagedOutletId($actor, $payload['outlet_id']);
        $weekStart = $this->resolveWeekStart($payload['week_start']);
        $shiftTemplateIds = collect($payload['days'])->filter()->values();

        if ($shiftTemplateIds->isEmpty()) {
            throw ValidationException::withMessages([
                'days' => 'Pilih minimal satu template shift untuk bulk assign mingguan.',
            ]);
        }

        $this->validateAssignmentReferences($outletId, $payload['user_id'], $shiftTemplateIds->all());

        DB::transaction(function () use ($payload, $weekStart, $outletId) {
            foreach ($payload['days'] as $offset => $shiftTemplateId) {
                if (!$shiftTemplateId) {
                    continue;
                }

                $this->employeeScheduleRepository->upsertSchedule(
                    $outletId,
                    $payload['user_id'],
                    $shiftTemplateId,
                    $weekStart->addDays((int) $offset),
                );
            }
        });
    }

    protected function resolveWeekStart(?string $value): CarbonImmutable
    {
        $date = $value ? CarbonImmutable::parse($value) : CarbonImmutable::today();

        return $date->startOfWeek();
    }

    protected function resolveManagedOutletId(User $actor, string $requestedOutletId): string
    {
        if ($actor->role?->type === 'supervisor') {
            if ($actor->outlet_id !== $requestedOutletId) {
                throw ValidationException::withMessages([
                    'outlet_id' => 'Supervisor hanya bisa mengelola jadwal pada outlet aktifnya.',
                ]);
            }

            return $actor->outlet_id;
        }

        return $requestedOutletId;
    }

    protected function validateAssignmentReferences(string $outletId, string $userId, array $shiftTemplateIds): void
    {
        $employee = $this->employeeScheduleRepository->findEmployeeForOutlet($userId, $outletId);

        if (!$employee) {
            throw ValidationException::withMessages([
                'user_id' => 'Karyawan tidak valid untuk outlet yang dipilih.',
            ]);
        }

        foreach ($shiftTemplateIds as $shiftTemplateId) {
            if (!$this->employeeScheduleRepository->findShiftTemplateForOutlet($shiftTemplateId, $outletId)) {
                throw ValidationException::withMessages([
                    'shift_template_id' => 'Template shift tidak valid untuk outlet yang dipilih.',
                ]);
            }
        }
    }

    protected function assertCanRead(User $actor): void
    {
        if (!in_array($actor->role?->type, ['owner', 'supervisor'], true)) {
            abort(403, 'Menu jadwal shift hanya tersedia untuk owner atau supervisor.');
        }
    }

    protected function assertCanManage(User $actor): void
    {
        $this->assertCanRead($actor);
    }
}
