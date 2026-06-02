<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\ExpenseRepository;
use App\Repositories\IncomeRepository;
use App\Repositories\SalesReportRepository;
use Carbon\CarbonImmutable;

class FinanceService
{
    public function __construct(
        protected SalesReportRepository $salesReportRepository,
        protected ExpenseRepository $expenseRepository,
        protected IncomeRepository $incomeRepository,
    ) {
    }

    public function getReport(User $actor, array $filters = []): array
    {
        $scopeOutletId = $this->resolveScopeOutletId($actor, $filters['outlet_id'] ?? null);
        $resolvedFilters = $this->resolveFilters($filters, $scopeOutletId);

        $currentStart = CarbonImmutable::parse($resolvedFilters['start_date']);
        $currentEnd = CarbonImmutable::parse($resolvedFilters['end_date']);

        // 1. Ambal data omzet penjualan lunas (Sales Revenue)
        $salesOrders = $this->salesReportRepository->getSettledOrdersSnapshot($resolvedFilters, $scopeOutletId);
        $totalSalesRevenue = (float) $salesOrders->sum('total_amount');

        // 2. Ambil data pengeluaran (Expenses)
        $expenses = $this->expenseRepository->getForPeriod($currentStart, $currentEnd, $scopeOutletId);
        $totalExpenses = (float) $expenses->sum('amount');

        // 3. Ambil data pemasukan non-penjualan (Other Incomes)
        $incomes = $this->incomeRepository->getForPeriod($currentStart, $currentEnd, $scopeOutletId);
        $totalOtherIncomes = (float) $incomes->sum('amount');

        // 4. Hitung untung bersih
        $netIncome = ($totalSalesRevenue + $totalOtherIncomes) - $totalExpenses;

        // 5. Data paginator untuk tabel-tabel di UI
        $expensesPaginated = $this->expenseRepository->paginate($resolvedFilters, $scopeOutletId);
        $incomesPaginated = $this->incomeRepository->paginate($resolvedFilters, $scopeOutletId);

        return [
            'summary' => [
                'total_sales' => $totalSalesRevenue,
                'total_other_incomes' => $totalOtherIncomes,
                'total_expenses' => $totalExpenses,
                'net_income' => $netIncome,
            ],
            'expenses' => $this->transformExpensePaginator($expensesPaginated),
            'incomes' => $this->transformIncomePaginator($incomesPaginated),
            'filters' => $resolvedFilters,
            'referenceData' => [
                'outlets' => $this->expenseRepository->getOutlets(
                    $actor->role?->type === 'owner' ? null : $scopeOutletId,
                ),
                'expenseCategories' => $this->expenseRepository->getCategories($scopeOutletId)->values()->all(),
                'incomeCategories' => $this->incomeRepository->getCategories($scopeOutletId)->values()->all(),
            ],
            'permissions' => [
                'canCreate' => in_array($actor->role?->type, ['owner', 'supervisor'], true),
                'canEdit' => $actor->role?->type === 'owner',
                'canDelete' => $actor->role?->type === 'owner',
            ],
        ];
    }

    protected function resolveFilters(array $filters, ?string $scopeOutletId): array
    {
        return [
            'start_date' => $filters['start_date'] ?? CarbonImmutable::today()->startOfMonth()->toDateString(),
            'end_date' => $filters['end_date'] ?? CarbonImmutable::today()->toDateString(),
            'outlet_id' => $scopeOutletId ?? '',
            'category' => $filters['category'] ?? '',
            'search' => trim((string) ($filters['search'] ?? '')),
            'per_page' => (int) ($filters['per_page'] ?? 10),
        ];
    }

    protected function resolveScopeOutletId(User $actor, ?string $requestedOutletId): ?string
    {
        if ($actor->role?->type === 'owner') {
            return $requestedOutletId ?: null;
        }

        return $actor->outlet_id;
    }

    protected function transformExpensePaginator($paginator)
    {
        $paginator->setCollection(
            $paginator->getCollection()->map(fn ($expense) => [
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
            ]),
        );

        return $paginator;
    }

    protected function transformIncomePaginator($paginator)
    {
        $paginator->setCollection(
            $paginator->getCollection()->map(fn ($income) => [
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
            ]),
        );

        return $paginator;
    }
}
