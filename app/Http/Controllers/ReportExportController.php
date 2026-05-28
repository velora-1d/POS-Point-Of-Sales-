<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportExportDownloadRequest;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ReportExportController extends Controller
{
    public function __construct(
        protected ReportExportService $reportExportService,
    ) {
    }

    public function index(Request $request): InertiaResponse
    {
        $data = $this->reportExportService->getPageData($request->user());

        return Inertia::render('Reports/Export', [
            'defaults' => $data['defaults'],
            'referenceData' => $data['referenceData'],
            'reportTypes' => $data['reportTypes'],
            'formats' => $data['formats'],
            'notes' => $data['notes'],
        ]);
    }

    public function download(ReportExportDownloadRequest $request): Response
    {
        return $this->reportExportService->download(
            $request->user(),
            $request->validated(),
        );
    }
}
