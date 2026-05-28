<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\User;
use App\Repositories\ExpenseRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpenseService
{
    public function __construct(
        protected ExpenseRepository $expenseRepository,
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

        $currentExpenses = $this->expenseRepository->getForPeriod(
            $currentStart,
            $currentEnd,
            $scopeOutletId,
            $categoryFilter,
        );
        $previousExpenses = $this->expenseRepository->getForPeriod(
            $previousPeriod['start'],
            $previousPeriod['end'],
            $scopeOutletId,
            $categoryFilter,
        );

        return [
            'summary' => $this->buildSummary($currentExpenses, $previousExpenses, $currentStart, $currentEnd),
            'categoryBreakdown' => $this->buildCategoryBreakdown($currentExpenses, $previousExpenses)->all(),
            'expenses' => $this->transformExpensePaginator(
                $this->expenseRepository->paginate($resolvedFilters, $scopeOutletId),
            ),
            'filters' => $resolvedFilters,
            'referenceData' => [
                'outlets' => $this->expenseRepository->getOutlets(
                    $actor->role?->type === 'owner' ? null : $scopeOutletId,
                ),
                'categories' => $this->expenseRepository->getCategories($scopeOutletId)->values()->all(),
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
            $this->expenseRepository->create([
                'outlet_id' => $outletId,
                'category' => $this->normalizeCategory($payload['category']),
                'description' => trim((string) $payload['description']),
                'amount' => (float) $payload['amount'],
                'expense_date' => $payload['expense_date'],
                'notes' => filled($payload['notes'] ?? null) ? trim((string) $payload['notes']) : null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
        });
    }

    public function update(Expense $expense, array $payload, User $actor): void
    {
        $this->assertCanEdit($actor);
        $scopedExpense = $this->expenseRepository->findForScope(
            $expense->id,
            $this->resolveScopeOutletId($actor, null),
        );

        if (!$scopedExpense) {
            abort(404);
        }

        $targetOutletId = $this->resolveTargetOutletId($actor, $payload['outlet_id'] ?? $scopedExpense->outlet_id);

        DB::transaction(function () use ($scopedExpense, $payload, $actor, $targetOutletId) {
            $this->expenseRepository->update($scopedExpense, [
                'outlet_id' => $targetOutletId,
                'category' => $this->normalizeCategory($payload['category']),
                'description' => trim((string) $payload['description']),
                'amount' => (float) $payload['amount'],
                'expense_date' => $payload['expense_date'],
                'notes' => filled($payload['notes'] ?? null) ? trim((string) $payload['notes']) : null,
                'updated_by' => $actor->id,
            ]);
        });
    }

    public function delete(Expense $expense, User $actor): void
    {
        $this->assertCanDelete($actor);
        $scopedExpense = $this->expenseRepository->findForScope($expense->id, $this->resolveScopeOutletId($actor, null));

        if (!$scopedExpense) {
            abort(404);
        }

        DB::transaction(function () use ($scopedExpense) {
            $this->expenseRepository->delete($scopedExpense);
        });
    }

    protected function transformExpensePaginator($paginator)
    {
        $paginator->setCollection(
            $paginator->getCollection()->map(fn (Expense $expense) => $this->transformExpense($expense)),
        );

        return $paginator;
    }

    protected function transformExpense(Expense $expense): array
    {
        return [
            'id' => $expense->id,
            'category' => $expense->category,
            'description' => $expense->description,
            'amount' => (float) $expense->amount,
            'expense_date' => $expense->expense_date?->toDateString(),
            'notes' => $expense->notes,
            'outlet' => $expense->outlet ? [
                'id' => $expense->outlet->id,
                'name' => $expense->outlet->name,
            ] : null,
            'creator_name' => $expense->creator?->name,
            'updater_name' => $expense->updater?->name,
            'created_at' => $expense->created_at?->toIso8601String(),
            'updated_at' => $expense->updated_at?->toIso8601String(),
        ];
    }

    protected function buildSummary(
        Collection $currentExpenses,
        Collection $previousExpenses,
        CarbonImmutable $currentStart,
        CarbonImmutable $currentEnd,
    ): array {
        $currentTotal = (float) $currentExpenses->sum(fn (Expense $expense) => (float) $expense->amount);
        $previousTotal = (float) $previousExpenses->sum(fn (Expense $expense) => (float) $expense->amount);
        $daySpan = max(1, $currentStart->diffInDays($currentEnd) + 1);
        $currentByCategory = $currentExpenses->groupBy('category');
        $largestCategory = $currentByCategory
            ->map(fn (Collection $group) => (float) $group->sum(fn (Expense $expense) => (float) $expense->amount))
            ->sortDesc()
            ->keys()
            ->first();

        return [
            'total_expenses' => $currentTotal,
            'previous_total_expenses' => $previousTotal,
            'growth_amount' => $currentTotal - $previousTotal,
            'growth_percentage' => $this->resolveGrowthPercentage($currentTotal, $previousTotal),
            'average_daily_expense' => $currentTotal / $daySpan,
            'entries_count' => $currentExpenses->count(),
            'categories_count' => $currentByCategory->count(),
            'largest_category' => $largestCategory ? [
                'name' => $largestCategory,
                'amount' => (float) $currentByCategory[$largestCategory]->sum(
                    fn (Expense $expense) => (float) $expense->amount,
                ),
            ] : null,
        ];
    }

    protected function buildCategoryBreakdown(Collection $currentExpenses, Collection $previousExpenses): Collection
    {
        $currentGrouped = $currentExpenses->groupBy('category');
        $previousGrouped = $previousExpenses->groupBy('category');
        $categories = $currentGrouped->keys()->merge($previousGrouped->keys())->unique()->values();

        return $categories->map(function (string $category) use ($currentGrouped, $previousGrouped) {
            /** @var Collection<int, Expense> $currentGroup */
            $currentGroup = $currentGrouped->get($category, collect());
            /** @var Collection<int, Expense> $previousGroup */
            $previousGroup = $previousGrouped->get($category, collect());
            $currentAmount = (float) $currentGroup->sum(fn (Expense $expense) => (float) $expense->amount);
            $previousAmount = (float) $previousGroup->sum(fn (Expense $expense) => (float) $expense->amount);

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
                    'outlet_id' => 'Owner wajib memilih outlet untuk mencatat pengeluaran.',
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
            abort(403, 'Pengeluaran operasional hanya tersedia untuk owner dan supervisor.');
        }
    }

    protected function assertCanCreate(User $actor): void
    {
        $this->assertCanRead($actor);
    }

    protected function assertCanEdit(User $actor): void
    {
        if ($actor->role?->type !== 'owner') {
            abort(403, 'Hanya owner yang boleh mengubah pengeluaran operasional.');
        }
    }

    protected function assertCanDelete(User $actor): void
    {
        if ($actor->role?->type !== 'owner') {
            abort(403, 'Hanya owner yang boleh menghapus pengeluaran operasional.');
        }
    }
}
