<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignEmployeeScheduleRequest;
use App\Http\Requests\AssignWeeklyEmployeeScheduleRequest;
use App\Services\EmployeeScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeScheduleController extends Controller
{
    public function __construct(
        protected EmployeeScheduleService $employeeScheduleService,
    ) {
    }

    public function index(Request $request): Response
    {
        $data = $this->employeeScheduleService->getDashboard(
            $request->user(),
            $request->only(['week_start', 'employee_id', 'outlet_id']),
        );

        return Inertia::render('Schedules/Index', [
            'summary' => $data['summary'],
            'employees' => $data['employees'],
            'shiftTemplates' => $data['shiftTemplates'],
            'todaySchedules' => $data['todaySchedules'],
            'schedules' => $data['schedules'],
            'filters' => $data['filters'],
            'days' => $data['days'],
            'referenceData' => $data['referenceData'],
            'canManage' => $data['canManage'],
            'success' => session('success'),
        ]);
    }

    public function store(AssignEmployeeScheduleRequest $request): RedirectResponse
    {
        $this->employeeScheduleService->assignDaily(
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('schedules.index', [
                'week_start' => $request->validated('schedule_date'),
                'outlet_id' => $request->validated('outlet_id'),
                'employee_id' => $request->validated('user_id'),
            ])
            ->with('success', 'Jadwal shift harian berhasil disimpan.');
    }

    public function bulkStore(AssignWeeklyEmployeeScheduleRequest $request): RedirectResponse
    {
        $this->employeeScheduleService->assignWeekly(
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('schedules.index', [
                'week_start' => $request->validated('week_start'),
                'outlet_id' => $request->validated('outlet_id'),
                'employee_id' => $request->validated('user_id'),
            ])
            ->with('success', 'Bulk assign jadwal mingguan berhasil disimpan.');
    }
}
