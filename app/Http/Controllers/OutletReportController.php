<?php

namespace App\Http\Controllers;

use App\Http\Requests\OutletReportIndexRequest;
use App\Services\OutletReportService;
use Inertia\Inertia;
use Inertia\Response;

class OutletReportController extends Controller
{
    public function __construct(
        protected OutletReportService $outletReportService,
    ) {
    }

    public function index(OutletReportIndexRequest $request): Response
    {
        $data = $this->outletReportService->getReport($request->user(), $request->validated());

        return Inertia::render('Reports/Outlets', [
            'summary' => $data['summary'],
            'outlets' => $data['outlets'],
            'filters' => $data['filters'],
            'period' => $data['period'],
        ]);
    }
}
