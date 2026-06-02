<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncomeRequest;
use App\Http\Requests\UpdateIncomeRequest;
use App\Models\Income;
use App\Services\IncomeService;
use Illuminate\Http\RedirectResponse;

class IncomeController extends Controller
{
    public function __construct(
        protected IncomeService $incomeService,
    ) {
    }

    public function store(StoreIncomeRequest $request): RedirectResponse
    {
        $this->incomeService->create(
            $request->validated(),
            $request->user(),
        );

        return redirect()->back()->with('success', 'Pemasukan operasional berhasil dicatat.');
    }

    public function update(UpdateIncomeRequest $request, Income $income): RedirectResponse
    {
        $this->incomeService->update(
            $income,
            $request->validated(),
            $request->user(),
        );

        return redirect()->back()->with('success', 'Pemasukan operasional berhasil diperbarui.');
    }

    public function destroy(Income $income): RedirectResponse
    {
        $this->incomeService->delete(
            $income,
            request()->user(),
        );

        return redirect()->back()->with('success', 'Pemasukan operasional berhasil dihapus.');
    }
}
