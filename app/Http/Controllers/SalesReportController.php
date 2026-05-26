<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalesReportIndexRequest;
use App\Services\SalesReportService;
use Inertia\Inertia;
use Inertia\Response;

class SalesReportController extends Controller
{
    public function __construct(
        protected SalesReportService $salesReportService,
    ) {
    }

    public function index(SalesReportIndexRequest $request): Response
    {
        $data = $this->salesReportService->getReport($request->user(), $request->validated());

        return Inertia::render('Reports/Sales', [
            'summary' => $data['summary'],
            'trend' => $data['trend'],
            'breakdowns' => $data['breakdowns'],
            'transactions' => $data['transactions'],
            'filters' => $data['filters'],
            'referenceData' => $data['referenceData'],
            'scope' => $data['scope'],
        ]);
    }
}
