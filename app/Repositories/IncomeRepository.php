<?php

namespace App\Repositories;

use App\Models\Income;
use App\Models\Outlet;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class IncomeRepository
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
        return Income::query()
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
            ->orderByDesc('income_date')
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
        return Income::query()
            ->with(['outlet', 'creator', 'updater'])
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->when($category, fn (Builder $query) => $query->where('category', $category))
            ->whereDate('income_date', '>=', $startDate->toDateString())
            ->whereDate('income_date', '<=', $endDate->toDateString())
            ->orderBy('income_date')
            ->get();
    }

    public function create(array $payload): Income
    {
        return Income::query()->create($payload);
    }

    public function update(Income $income, array $payload): Income
    {
        $income->update($payload);

        return $income->load(['outlet', 'creator', 'updater']);
    }

    public function delete(Income $income): void
    {
        $income->delete();
    }

    public function findForScope(string $incomeId, ?string $scopeOutletId = null): ?Income
    {
        return Income::query()
            ->with(['outlet', 'creator', 'updater'])
            ->whereKey($incomeId)
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->first();
    }

    protected function baseQuery(array $filters = [], ?string $scopeOutletId = null): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $category = $filters['category'] ?? null;
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        return Income::query()
            ->with(['outlet', 'creator', 'updater'])
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->when($category, fn (Builder $query) => $query->where('category', $category))
            ->when($startDate, fn (Builder $query) => $query->whereDate('income_date', '>=', $startDate))
            ->when($endDate, fn (Builder $query) => $query->whereDate('income_date', '<=', $endDate))
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
