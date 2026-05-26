<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClockInAttendanceRequest;
use App\Http\Requests\ClockOutAttendanceRequest;
use App\Http\Requests\UpdateAttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService,
    ) {
    }

    public function index(Request $request): Response
    {
        $data = $this->attendanceService->getDashboard(
            $request->user(),
            $request->only(['status', 'user_id', 'outlet_id', 'start_date', 'end_date', 'per_page']),
        );

        return Inertia::render('Attendance/Index', [
            'summary' => $data['summary'],
            'attendanceReport' => $data['attendanceReport'],
            'selfAttendance' => $data['selfAttendance'],
            'todaySchedule' => $data['todaySchedule'],
            'recentAttendances' => $data['recentAttendances'],
            'todayEntries' => $data['todayEntries'],
            'attendances' => $data['attendances'],
            'filters' => $data['filters'],
            'referenceData' => $data['referenceData'],
            'canManage' => $data['canManage'],
            'canClock' => $data['canClock'],
            'success' => session('success'),
        ]);
    }

    public function clockIn(ClockInAttendanceRequest $request): RedirectResponse
    {
        $this->attendanceService->clockIn(
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('attendance.index')
            ->with('success', 'Clock in berhasil dicatat.');
    }

    public function clockOut(ClockOutAttendanceRequest $request): RedirectResponse
    {
        $this->attendanceService->clockOut(
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('attendance.index')
            ->with('success', 'Clock out berhasil dicatat.');
    }

    public function update(UpdateAttendanceCorrectionRequest $request, Attendance $attendance): RedirectResponse
    {
        $this->attendanceService->correct(
            $attendance,
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('attendance.index', $request->only(['status', 'user_id', 'outlet_id', 'start_date', 'end_date']))
            ->with('success', 'Koreksi absensi berhasil disimpan.');
    }
}
