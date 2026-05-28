<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryReportIndexRequest;
use App\Services\InventoryReportService;
use Inertia\Inertia;
use Inertia\Response;

class InventoryReportController extends Controller
{
    public function __construct(
        protected InventoryReportService $inventoryReportService,
    ) {
    }

    public function index(InventoryReportIndexRequest $request): Response
    {
        $data = $this->inventoryReportService->getReport($request->user(), $request->validated());

        return Inertia::render('Reports/Inventory', [
            'summary' => $data['summary'],
            'inventory' => $data['inventory'],
            'expiries' => $data['expiries'],
            'filters' => $data['filters'],
            'referenceData' => $data['referenceData'],
            'period' => $data['period'],
            'limitations' => $data['limitations'],
        ]);
    }
}
