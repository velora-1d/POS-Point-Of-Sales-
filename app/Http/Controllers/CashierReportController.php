<?php

namespace App\Http\Controllers;

use App\Http\Requests\CashierReportIndexRequest;
use App\Services\CashierReportService;
use Inertia\Inertia;
use Inertia\Response;

class CashierReportController extends Controller
{
    public function __construct(
        protected CashierReportService $cashierReportService,
    ) {
    }

    public function index(CashierReportIndexRequest $request): Response
    {
        $data = $this->cashierReportService->getReport($request->user(), $request->validated());

        return Inertia::render('Reports/Cashiers', [
            'summary' => $data['summary'],
            'cashiers' => $data['cashiers'],
            'filters' => $data['filters'],
            'referenceData' => $data['referenceData'],
            'period' => $data['period'],
        ]);
    }
}
