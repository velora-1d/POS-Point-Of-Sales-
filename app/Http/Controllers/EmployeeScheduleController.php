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

    public function updateShiftTimes(Request $request): RedirectResponse
    {
        $request->validate([
            'pagi_start' => ['required', 'string'],
            'pagi_end' => ['required', 'string'],
            'malam_start' => ['required', 'string'],
            'malam_end' => ['required', 'string'],
            'outlet_id' => ['required', 'exists:outlets,id'],
        ]);

        $templates = \App\Models\ShiftTemplate::where('outlet_id', $request->input('outlet_id'))->get();
        foreach ($templates as $template) {
            if (str_contains(strtolower($template->name), 'pagi')) {
                $template->update([
                    'start_time' => $request->input('pagi_start') . ':00',
                    'end_time' => $request->input('pagi_end') . ':00',
                ]);
            } elseif (str_contains(strtolower($template->name), 'malam')) {
                $template->update([
                    'start_time' => $request->input('malam_start') . ':00',
                    'end_time' => $request->input('malam_end') . ':00',
                ]);
            }
        }

        return redirect()
            ->back()
            ->with('success', 'Waktu shift berhasil diperbarui secara global.');
    }
}
