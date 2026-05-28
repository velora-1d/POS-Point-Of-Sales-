<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseReportIndexRequest;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function __construct(
        protected ExpenseService $expenseService,
    ) {
    }

    public function index(ExpenseReportIndexRequest $request): Response
    {
        $data = $this->expenseService->getReport($request->user(), $request->validated());

        return Inertia::render('Reports/Expenses', [
            'summary' => $data['summary'],
            'categoryBreakdown' => $data['categoryBreakdown'],
            'expenses' => $data['expenses'],
            'filters' => $data['filters'],
            'referenceData' => $data['referenceData'],
            'period' => $data['period'],
            'permissions' => $data['permissions'],
            'success' => session('success'),
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $this->expenseService->create(
            $request->validated(),
            $request->user(),
        );

        return redirect()->back()->with('success', 'Pengeluaran operasional berhasil dicatat.');
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->expenseService->update(
            $expense,
            $request->validated(),
            $request->user(),
        );

        return redirect()->back()->with('success', 'Pengeluaran operasional berhasil diperbarui.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->expenseService->delete(
            $expense,
            request()->user(),
        );

        return redirect()->back()->with('success', 'Pengeluaran operasional berhasil dihapus.');
    }
}
