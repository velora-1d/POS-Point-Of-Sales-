<?php

namespace App\Http\Controllers;

use App\Services\FinanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinanceController extends Controller
{
    public function __construct(
        protected FinanceService $financeService,
    ) {
    }

    public function index(Request $request): Response
    {
        $data = $this->financeService->getReport($request->user(), $request->all());

        return Inertia::render('Reports/Expenses', [
            'summary' => $data['summary'],
            'expenses' => $data['expenses'],
            'incomes' => $data['incomes'],
            'filters' => $data['filters'],
            'referenceData' => $data['referenceData'],
            'permissions' => $data['permissions'],
            'success' => session('success'),
        ]);
    }
}
