<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\User;
use App\Repositories\ShiftFinancialReportRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ShiftFinancialReportService
{
    protected const PAYMENT_METHODS = ['cash', 'qris', 'debit', 'ewallet', 'kasbon'];

    public function __construct(
        protected ShiftFinancialReportRepository $repo,
    ) {}

    public function getReport(User $actor, array $filters = []): array
    {
        $this->assertCanRead($actor);

        $outletId       = $this->resolveScopeOutletId($actor, $filters['outlet_id'] ?? null);
        $resolved       = $this->resolveFilters($filters, $outletId);
        $startDate      = CarbonImmutable::parse($resolved['start_date']);
        $endDate        = CarbonImmutable::parse($resolved['end_date']);

        $shifts = $this->repo->getShiftsForReport(
            $startDate,
            $endDate,
            $outletId,
            $resolved['user_id'] ?: null,
        );

        $dailyGroups = $this->buildDailyGroups($shifts);
        $summary     = $this->buildSummary($shifts);

        return [
            'summary'       => $summary,
            'dailyGroups'   => $dailyGroups,
            'filters'       => $resolved,
            'referenceData' => [
                'outlets'  => $this->repo->getOutlets(
                    $actor->role?->type === 'owner' ? null : $outletId,
                ),
                'cashiers' => $this->repo->getCashierUsers($outletId),
            ],
        ];
    }

    // ─── Filters ────────────────────────────────────────────────────────────

    protected function resolveFilters(array $filters, ?string $outletId): array
    {
        $period = $filters['period'] ?? 'daily';
        [$startDate, $endDate] = $this->resolveDateRange($period, $filters);

        return [
            'period'     => $period,
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'outlet_id'  => $outletId ?? '',
            'user_id'    => $filters['user_id'] ?? '',
        ];
    }

    protected function resolveDateRange(string $period, array $filters): array
    {
        $today = CarbonImmutable::today();

        return match ($period) {
            'shift' => [
                $filters['start_date'] ?? $today->toDateString(),
                $filters['end_date']   ?? $today->toDateString(),
            ],
            'daily' => [
                $filters['date'] ?? $today->toDateString(),
                $filters['date'] ?? $today->toDateString(),
            ],
            'weekly' => [
                $today->startOfWeek()->toDateString(),
                $today->endOfWeek()->toDateString(),
            ],
            'monthly' => [
                $today->startOfMonth()->toDateString(),
                $today->endOfMonth()->toDateString(),
            ],
            'quarterly' => [
                $today->startOfQuarter()->toDateString(),
                $today->endOfQuarter()->toDateString(),
            ],
            'yearly' => [
                $today->startOfYear()->toDateString(),
                $today->endOfYear()->toDateString(),
            ],
            default => [
                $filters['start_date'] ?? $today->startOfMonth()->toDateString(),
                $filters['end_date']   ?? $today->toDateString(),
            ],
        };
    }

    // ─── Grouping & Building ─────────────────────────────────────────────────

    protected function buildDailyGroups(Collection $shifts): array
    {
        return $shifts
            ->groupBy(fn (Shift $shift) => $shift->opened_at->toDateString())
            ->map(function (Collection $dayShifts, string $date) {
                $shiftRows = $dayShifts->map(fn (Shift $shift) => $this->buildShiftRow($shift))->values()->all();

                $dayRevenue  = array_sum(array_column($shiftRows, 'total_revenue'));
                $dayOrders   = array_sum(array_column($shiftRows, 'total_orders'));
                $dayDiscount = array_sum(array_column($shiftRows, 'total_discount'));

                $dayBreakdown = array_fill_keys(self::PAYMENT_METHODS, 0.0);
                foreach ($shiftRows as $row) {
                    foreach ($row['breakdown'] as $method => $amount) {
                        $dayBreakdown[$method] = ($dayBreakdown[$method] ?? 0) + $amount;
                    }
                }

                return [
                    'date'          => $date,
                    'date_label'    => CarbonImmutable::parse($date)->locale('id')->isoFormat('dddd, D MMMM YYYY'),
                    'total_shifts'  => count($shiftRows),
                    'total_revenue' => $dayRevenue,
                    'total_orders'  => $dayOrders,
                    'total_discount'=> $dayDiscount,
                    'breakdown'     => $dayBreakdown,
                    'shifts'        => $shiftRows,
                ];
            })
            ->sortKeysDesc()
            ->values()
            ->all();
    }

    protected function buildShiftRow(Shift $shift): array
    {
        $report = $shift->cashReport;

        $totalRevenue   = (float) ($report?->total_revenue  ?? 0);
        $totalOrders    = (int)   ($report?->total_orders   ?? 0);
        $totalDiscount  = (float) ($report?->total_discount ?? 0);
        $totalRefund    = (float) ($report?->total_refund   ?? 0);

        $breakdown = [
            'cash'    => (float) ($report?->total_cash    ?? 0),
            'qris'    => (float) ($report?->total_qris    ?? 0),
            'debit'   => (float) ($report?->total_debit   ?? 0),
            'ewallet' => (float) ($report?->total_ewallet ?? 0),
            'kasbon'  => (float) ($report?->total_kasbon  ?? 0),
        ];

        $openingCash    = (float) ($shift->opening_cash    ?? 0);
        $expectedCash   = (float) ($shift->expected_cash   ?? $openingCash);
        $actualCash     = $shift->actual_cash !== null ? (float) $shift->actual_cash : null;
        $cashDifference = $shift->cash_difference !== null ? (float) $shift->cash_difference : null;

        return [
            'id'                  => $shift->id,
            'status'              => $shift->status,
            'shift_number_of_day' => (int) ($shift->shift_number_of_day ?? 1),
            'opened_at'           => $shift->opened_at?->toIso8601String(),
            'closed_at'           => $shift->closed_at?->toIso8601String(),
            'duration_minutes'    => $shift->closed_at
                ? (int) $shift->opened_at->diffInMinutes($shift->closed_at)
                : null,
            'shift_template'      => $shift->shiftTemplate ? [
                'id'         => $shift->shiftTemplate->id,
                'name'       => $shift->shiftTemplate->name,
                'start_time' => $shift->shiftTemplate->start_time,
                'end_time'   => $shift->shiftTemplate->end_time,
            ] : null,
            'cashier' => $shift->user ? [
                'id'   => $shift->user->id,
                'name' => $shift->user->name,
                'role' => $shift->user->role?->name,
            ] : null,
            'opener' => $shift->opener ? ['name' => $shift->opener->name] : null,
            'closer' => $shift->closer ? ['name' => $shift->closer->name] : null,
            'outlet' => $shift->outlet ? [
                'id'   => $shift->outlet->id,
                'name' => $shift->outlet->name,
            ] : null,
            'opening_cash'    => $openingCash,
            'expected_cash'   => $expectedCash,
            'actual_cash'     => $actualCash,
            'cash_difference' => $cashDifference,
            'total_revenue'   => $totalRevenue,
            'total_orders'    => $totalOrders,
            'total_discount'  => $totalDiscount,
            'total_refund'    => $totalRefund,
            'breakdown'       => $breakdown,
            'notes'           => $shift->notes,
        ];
    }

    protected function buildSummary(Collection $shifts): array
    {
        $closed = $shifts->where('status', 'closed');

        $totalRevenue  = (float) $closed->sum(fn (Shift $s) => (float) ($s->cashReport?->total_revenue ?? 0));
        $totalOrders   = (int)   $closed->sum(fn (Shift $s) => (int)   ($s->cashReport?->total_orders  ?? 0));
        $totalDiscount = (float) $closed->sum(fn (Shift $s) => (float) ($s->cashReport?->total_discount ?? 0));

        $breakdown = array_fill_keys(self::PAYMENT_METHODS, 0.0);
        foreach ($closed as $shift) {
            $breakdown['cash']    += (float) ($shift->cashReport?->total_cash    ?? 0);
            $breakdown['qris']    += (float) ($shift->cashReport?->total_qris    ?? 0);
            $breakdown['debit']   += (float) ($shift->cashReport?->total_debit   ?? 0);
            $breakdown['ewallet'] += (float) ($shift->cashReport?->total_ewallet ?? 0);
            $breakdown['kasbon']  += (float) ($shift->cashReport?->total_kasbon  ?? 0);
        }

        $cashDiffTotal   = (float) $closed->sum(fn (Shift $s) => (float) ($s->cash_difference ?? 0));
        $totalShifts     = $shifts->count();
        $closedShifts    = $closed->count();
        $avgRevenueShift = $closedShifts > 0 ? $totalRevenue / $closedShifts : 0;

        return [
            'total_shifts'       => $totalShifts,
            'closed_shifts'      => $closedShifts,
            'active_shifts'      => $shifts->where('status', 'active')->count(),
            'total_revenue'      => $totalRevenue,
            'total_orders'       => $totalOrders,
            'total_discount'     => $totalDiscount,
            'avg_revenue_shift'  => $avgRevenueShift,
            'cash_difference'    => $cashDiffTotal,
            'breakdown'          => $breakdown,
        ];
    }

    // ─── Guards ──────────────────────────────────────────────────────────────

    protected function resolveScopeOutletId(User $actor, ?string $requestedOutletId): ?string
    {
        if ($actor->role?->type === 'owner') {
            return $requestedOutletId ?: null;
        }

        return $actor->outlet_id;
    }

    protected function assertCanRead(User $actor): void
    {
        if (!in_array($actor->role?->type, ['owner', 'supervisor'], true)) {
            abort(403, 'Laporan keuangan shift hanya tersedia untuk owner dan supervisor.');
        }
    }
}
