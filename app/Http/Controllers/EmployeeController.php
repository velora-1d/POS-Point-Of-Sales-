<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\User;
use App\Services\EmployeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function __construct(
        protected EmployeeService $employeeService,
    ) {
    }

    public function index(Request $request): Response
    {
        $data = $this->employeeService->getDashboard(
            $request->user(),
            $request->only(['search', 'status', 'role_type', 'outlet_id', 'per_page']),
        );

        return Inertia::render('Employees/Index', [
            'employees' => $data['employees'],
            'summary' => $data['summary'],
            'roleBreakdown' => $data['roleBreakdown'],
            'filters' => $data['filters'],
            'referenceData' => $data['referenceData'],
            'canManage' => $data['canManage'],
            'success' => session('success'),
        ]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $this->employeeService->create(
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('employees.index')
            ->with('success', 'Data karyawan berhasil ditambahkan.');
    }

    public function update(UpdateEmployeeRequest $request, User $employee): RedirectResponse
    {
        $this->employeeService->update(
            $employee,
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('employees.index')
            ->with('success', 'Data karyawan berhasil diperbarui.');
    }
}
