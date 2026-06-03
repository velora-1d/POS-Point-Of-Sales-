<?php

namespace App\Http\Controllers;

use App\Services\ShiftFinancialReportService;
use Illuminate\Http\Request;
use Inertia\Response;
use Inertia\Inertia;

class ShiftFinancialReportController extends Controller
{
    public function __construct(
        protected ShiftFinancialReportService $service,
    ) {}

    public function index(Request $request): Response
    {
        $data = $this->service->getReport(
            $request->user(),
            $request->only([
                'period',
                'date',
                'start_date',
                'end_date',
                'outlet_id',
                'user_id',
            ]),
        );

        return Inertia::render('Reports/ShiftFinance', [
            'summary'       => $data['summary'],
            'dailyGroups'   => $data['dailyGroups'],
            'filters'       => $data['filters'],
            'referenceData' => $data['referenceData'],
        ]);
    }
}
