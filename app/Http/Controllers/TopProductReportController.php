<?php

namespace App\Http\Controllers;

use App\Http\Requests\TopProductReportIndexRequest;
use App\Services\TopProductReportService;
use Inertia\Inertia;
use Inertia\Response;

class TopProductReportController extends Controller
{
    public function __construct(
        protected TopProductReportService $topProductReportService,
    ) {
    }

    public function index(TopProductReportIndexRequest $request): Response
    {
        $data = $this->topProductReportService->getReport($request->user(), $request->validated());

        return Inertia::render('Reports/TopProducts', [
            'summary' => $data['summary'],
            'products' => $data['products'],
            'filters' => $data['filters'],
            'referenceData' => $data['referenceData'],
            'period' => $data['period'],
        ]);
    }
}
