<?php

namespace App\Services;

use App\Models\Income;
use App\Models\User;
use App\Repositories\IncomeRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IncomeService
{
    public function __construct(
        protected IncomeRepository $incomeRepository,
    ) {
    }

    public function getReport(User $actor, array $filters = []): array
    {
        $this->assertCanRead($actor);

        $scopeOutletId = $this->resolveScopeOutletId($actor, $filters['outlet_id'] ?? null);
        $resolvedFilters = $this->resolveFilters($filters, $scopeOutletId);
        $currentStart = CarbonImmutable::parse($resolvedFilters['start_date']);
        $currentEnd = CarbonImmutable::parse($resolvedFilters['end_date']);
        $previousPeriod = $this->resolvePreviousPeriod($currentStart, $currentEnd);
        $categoryFilter = $resolvedFilters['category'] ?: null;

        $currentIncomes = $this->incomeRepository->getForPeriod(
            $currentStart,
            $currentEnd,
            $scopeOutletId,
            $categoryFilter,
        );
        $previousIncomes = $this->incomeRepository->getForPeriod(
            $previousPeriod['start'],
            $previousPeriod['end'],
            $scopeOutletId,
            $categoryFilter,
        );

        return [
            'summary' => $this->buildSummary($currentIncomes, $previousIncomes, $currentStart, $currentEnd),
            'categoryBreakdown' => $this->buildCategoryBreakdown($currentIncomes, $previousIncomes)->all(),
            'incomes' => $this->transformIncomePaginator(
                $this->incomeRepository->paginate($resolvedFilters, $scopeOutletId),
            ),
            'filters' => $resolvedFilters,
            'referenceData' => [
                'outlets' => $this->incomeRepository->getOutlets(
                    $actor->role?->type === 'owner' ? null : $scopeOutletId,
                ),
                'categories' => $this->incomeRepository->getCategories($scopeOutletId)->values()->all(),
            ],
            'period' => [
                'current' => [
                    'start_date' => $currentStart->toDateString(),
                    'end_date' => $currentEnd->toDateString(),
                ],
                'previous' => [
                    'start_date' => $previousPeriod['start']->toDateString(),
                    'end_date' => $previousPeriod['end']->toDateString(),
                ],
            ],
            'permissions' => [
                'canCreate' => in_array($actor->role?->type, ['owner', 'supervisor'], true),
                'canEdit' => $actor->role?->type === 'owner',
                'canDelete' => $actor->role?->type === 'owner',
            ],
        ];
    }

    public function create(array $payload, User $actor): void
    {
        $this->assertCanCreate($actor);
        $outletId = $this->resolveTargetOutletId($actor, $payload['outlet_id'] ?? null);

        DB::transaction(function () use ($payload, $actor, $outletId) {
            $this->incomeRepository->create([
                'outlet_id' => $outletId,
                'category' => $this->normalizeCategory($payload['category']),
                'description' => trim((string) $payload['description']),
                'amount' => (float) $payload['amount'],
                'income_date' => $payload['income_date'],
                'notes' => filled($payload['notes'] ?? null) ? trim((string) $payload['notes']) : null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
        });
    }

    public function update(Income $income, array $payload, User $actor): void
    {
        $this->assertCanEdit($actor);
        $scopedIncome = $this->incomeRepository->findForScope(
            $income->id,
            $this->resolveScopeOutletId($actor, null),
        );

        if (!$scopedIncome) {
            abort(404);
        }

        $targetOutletId = $this->resolveTargetOutletId($actor, $payload['outlet_id'] ?? $scopedIncome->outlet_id);

        DB::transaction(function () use ($scopedIncome, $payload, $actor, $targetOutletId) {
            $this->incomeRepository->update($scopedIncome, [
                'outlet_id' => $targetOutletId,
                'category' => $this->normalizeCategory($payload['category']),
                'description' => trim((string) $payload['description']),
                'amount' => (float) $payload['amount'],
                'income_date' => $payload['income_date'],
                'notes' => filled($payload['notes'] ?? null) ? trim((string) $payload['notes']) : null,
                'updated_by' => $actor->id,
            ]);
        });
    }

    public function delete(Income $income, User $actor): void
    {
        $this->assertCanDelete($actor);
        $scopedIncome = $this->incomeRepository->findForScope($income->id, $this->resolveScopeOutletId($actor, null));

        if (!$scopedIncome) {
            abort(404);
        }

        DB::transaction(function () use ($scopedIncome) {
            $this->incomeRepository->delete($scopedIncome);
        });
    }

    protected function transformIncomePaginator($paginator)
    {
        $paginator->setCollection(
            $paginator->getCollection()->map(fn (Income $income) => $this->transformIncome($income)),
        );

        return $paginator;
    }

    protected function transformIncome(Income $income): array
    {
        return [
            'id' => $income->id,
            'category' => $income->category,
            'description' => $income->description,
            'amount' => (float) $income->amount,
            'income_date' => $income->income_date?->toDateString(),
            'notes' => $income->notes,
            'outlet' => $income->outlet ? [
                'id' => $income->outlet->id,
                'name' => $income->outlet->name,
            ] : null,
            'creator_name' => $income->creator?->name,
            'updater_name' => $income->updater?->name,
            'created_at' => $income->created_at?->toIso8601String(),
            'updated_at' => $income->updated_at?->toIso8601String(),
        ];
    }

    protected function buildSummary(
        Collection $currentIncomes,
        Collection $previousIncomes,
        CarbonImmutable $currentStart,
        CarbonImmutable $currentEnd,
    ): array {
        $currentTotal = (float) $currentIncomes->sum(fn (Income $income) => (float) $income->amount);
        $previousTotal = (float) $previousIncomes->sum(fn (Income $income) => (float) $income->amount);
        $daySpan = max(1, $currentStart->diffInDays($currentEnd) + 1);
        $currentByCategory = $currentIncomes->groupBy('category');
        $largestCategory = $currentByCategory
            ->map(fn (Collection $group) => (float) $group->sum(fn (Income $income) => (float) $income->amount))
            ->sortDesc()
            ->keys()
            ->first();

        return [
            'total_incomes' => $currentTotal,
            'previous_total_incomes' => $previousTotal,
            'growth_amount' => $currentTotal - $previousTotal,
            'growth_percentage' => $this->resolveGrowthPercentage($currentTotal, $previousTotal),
            'average_daily_income' => $currentTotal / $daySpan,
            'entries_count' => $currentIncomes->count(),
            'categories_count' => $currentByCategory->count(),
            'largest_category' => $largestCategory ? [
                'name' => $largestCategory,
                'amount' => (float) $currentByCategory[$largestCategory]->sum(
                    fn (Income $income) => (float) $income->amount,
                ),
            ] : null,
        ];
    }

    protected function buildCategoryBreakdown(Collection $currentIncomes, Collection $previousIncomes): Collection
    {
        $currentGrouped = $currentIncomes->groupBy('category');
        $previousGrouped = $previousIncomes->groupBy('category');
        $categories = $currentGrouped->keys()->merge($previousGrouped->keys())->unique()->values();

        return $categories->map(function (string $category) use ($currentGrouped, $previousGrouped) {
            /** @var Collection<int, Income> $currentGroup */
            $currentGroup = $currentGrouped->get($category, collect());
            /** @var Collection<int, Income> $previousGroup */
            $previousGroup = $previousGrouped->get($category, collect());
            $currentAmount = (float) $currentGroup->sum(fn (Income $income) => (float) $income->amount);
            $previousAmount = (float) $previousGroup->sum(fn (Income $income) => (float) $income->amount);

            return [
                'category' => $category,
                'current_amount' => $currentAmount,
                'current_count' => $currentGroup->count(),
                'previous_amount' => $previousAmount,
                'difference_amount' => $currentAmount - $previousAmount,
                'growth_percentage' => $this->resolveGrowthPercentage($currentAmount, $previousAmount),
            ];
        })->sortByDesc('current_amount')->values();
    }

    protected function resolveFilters(array $filters, ?string $scopeOutletId): array
    {
        return [
            'start_date' => $filters['start_date'] ?? CarbonImmutable::today()->startOfMonth()->toDateString(),
            'end_date' => $filters['end_date'] ?? CarbonImmutable::today()->toDateString(),
            'outlet_id' => $scopeOutletId ?? '',
            'category' => $filters['category'] ?? '',
            'search' => trim((string) ($filters['search'] ?? '')),
            'per_page' => (int) ($filters['per_page'] ?? 12),
        ];
    }

    protected function resolvePreviousPeriod(CarbonImmutable $currentStart, CarbonImmutable $currentEnd): array
    {
        $daySpan = $currentStart->diffInDays($currentEnd);
        $previousEnd = $currentStart->subDay();
        $previousStart = $previousEnd->subDays($daySpan);

        return [
            'start' => $previousStart,
            'end' => $previousEnd,
        ];
    }

    protected function resolveGrowthPercentage(float $currentValue, float $previousValue): ?float
    {
        if ($previousValue <= 0) {
            return $currentValue > 0 ? null : 0.0;
        }

        return (($currentValue - $previousValue) / $previousValue) * 100;
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

    protected function resolveTargetOutletId(User $actor, ?string $requestedOutletId): string
    {
        if ($actor->role?->type === 'owner') {
            $targetOutletId = $requestedOutletId ?: $actor->outlet_id;

            if (!$targetOutletId) {
                throw ValidationException::withMessages([
                    'outlet_id' => 'Owner wajib memilih outlet untuk mencatat pemasukan.',
                ]);
            }

            return $targetOutletId;
        }

        if (!$actor->outlet_id) {
            throw ValidationException::withMessages([
                'outlet_id' => 'Supervisor belum terhubung ke outlet aktif.',
            ]);
        }

        return $actor->outlet_id;
    }

    protected function normalizeCategory(string $category): string
    {
        return trim((string) str($category)->lower()->replaceMatches('/\s+/', ' '));
    }

    protected function assertCanRead(User $actor): void
    {
        if (!in_array($actor->role?->type, ['owner', 'supervisor'], true)) {
            abort(403, 'Pemasukan operasional hanya tersedia untuk owner dan supervisor.');
        }
    }

    protected function assertCanCreate(User $actor): void
    {
        $this->assertCanRead($actor);
    }

    protected function assertCanEdit(User $actor): void
    {
        if ($actor->role?->type !== 'owner') {
            abort(403, 'Hanya owner yang boleh mengubah pemasukan operasional.');
        }
    }

    protected function assertCanDelete(User $actor): void
    {
        if ($actor->role?->type !== 'owner') {
            abort(403, 'Hanya owner yang boleh menghapus pemasukan operasional.');
        }
    }
}
