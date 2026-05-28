<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceShiftReportIndexRequest;
use App\Services\AttendanceShiftReportService;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceShiftReportController extends Controller
{
    public function __construct(
        protected AttendanceShiftReportService $attendanceShiftReportService,
    ) {
    }

    public function index(AttendanceShiftReportIndexRequest $request): Response
    {
        $data = $this->attendanceShiftReportService->getReport($request->user(), $request->validated());

        return Inertia::render('Reports/AttendanceShift', [
            'summary' => $data['summary'],
            'employees' => $data['employees'],
            'missingAttendances' => $data['missingAttendances'],
            'shiftAnomalies' => $data['shiftAnomalies'],
            'filters' => $data['filters'],
            'referenceData' => $data['referenceData'],
            'period' => $data['period'],
            'limitations' => $data['limitations'],
        ]);
    }
}
