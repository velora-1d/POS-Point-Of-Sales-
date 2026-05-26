<?php

namespace App\Http\Controllers;

use App\Http\Requests\CloseShiftRequest;
use App\Http\Requests\OpenShiftRequest;
use App\Models\Shift;
use App\Services\ShiftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShiftController extends Controller
{
    public function __construct(
        protected ShiftService $shiftService,
    ) {
    }

    public function index(Request $request): Response
    {
        $data = $this->shiftService->getDashboard(
            $request->user(),
            $request->only(['status', 'user_id', 'outlet_id', 'start_date', 'end_date', 'per_page']),
        );

        return Inertia::render('Shifts/Index', [
            'activeShift' => $data['activeShift'],
            'lastClosedShift' => $data['lastClosedShift'],
            'todaySchedule' => $data['todaySchedule'],
            'shiftTemplates' => $data['shiftTemplates'],
            'cashRecap' => $data['cashRecap'],
            'history' => $data['history'],
            'filters' => $data['filters'],
            'referenceData' => $data['referenceData'],
            'canManage' => $data['canManage'],
            'canRead' => $data['canRead'],
            'success' => session('success'),
        ]);
    }

    public function open(OpenShiftRequest $request): RedirectResponse
    {
        $this->shiftService->open(
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('shifts.index')
            ->with('success', 'Shift kasir berhasil dibuka.');
    }

    public function close(CloseShiftRequest $request, Shift $shift): RedirectResponse
    {
        $this->shiftService->close(
            $shift,
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('shifts.index')
            ->with('success', 'Shift kasir berhasil ditutup.');
    }
}
