<?php

namespace App\Repositories;

use App\Models\Expense;
use App\Models\Outlet;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ExpenseRepository
{
    public function getOutlets(?string $scopeOutletId = null): Collection
    {
        return Outlet::query()
            ->where('is_active', true)
            ->when($scopeOutletId, fn (Builder $query) => $query->whereKey($scopeOutletId))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getCategories(?string $scopeOutletId = null): Collection
    {
        return Expense::query()
            ->select('category')
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
    }

    public function paginate(array $filters = [], ?string $scopeOutletId = null): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 12);

        return $this->baseQuery($filters, $scopeOutletId)
            ->orderByDesc('expense_date')
            ->orderByDesc('created_at')
            ->paginate(max(6, min($perPage, 30)))
            ->withQueryString();
    }

    public function getForPeriod(
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?string $scopeOutletId = null,
        ?string $category = null,
    ): Collection {
        return Expense::query()
            ->with(['outlet', 'creator', 'updater'])
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->when($category, fn (Builder $query) => $query->where('category', $category))
            ->whereDate('expense_date', '>=', $startDate->toDateString())
            ->whereDate('expense_date', '<=', $endDate->toDateString())
            ->orderBy('expense_date')
            ->get();
    }

    public function create(array $payload): Expense
    {
        return Expense::query()->create($payload);
    }

    public function update(Expense $expense, array $payload): Expense
    {
        $expense->update($payload);

        return $expense->load(['outlet', 'creator', 'updater']);
    }

    public function delete(Expense $expense): void
    {
        $expense->delete();
    }

    public function findForScope(string $expenseId, ?string $scopeOutletId = null): ?Expense
    {
        return Expense::query()
            ->with(['outlet', 'creator', 'updater'])
            ->whereKey($expenseId)
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->first();
    }

    protected function baseQuery(array $filters = [], ?string $scopeOutletId = null): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $category = $filters['category'] ?? null;
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        return Expense::query()
            ->with(['outlet', 'creator', 'updater'])
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->when($category, fn (Builder $query) => $query->where('category', $category))
            ->when($startDate, fn (Builder $query) => $query->whereDate('expense_date', '>=', $startDate))
            ->when($endDate, fn (Builder $query) => $query->whereDate('expense_date', '<=', $endDate))
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $innerQuery) use ($search) {
                    $innerQuery
                        ->where('category', 'ilike', '%' . $search . '%')
                        ->orWhere('description', 'ilike', '%' . $search . '%')
                        ->orWhere('notes', 'ilike', '%' . $search . '%');
                });
            });
    }
}
