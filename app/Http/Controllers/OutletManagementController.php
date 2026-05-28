<?php

namespace App\Http\Controllers;

use App\Http\Requests\OutletManagementIndexRequest;
use App\Http\Requests\StoreOutletRequest;
use App\Http\Requests\UpdateOutletRequest;
use App\Http\Requests\UpdateOutletStatusRequest;
use App\Models\Outlet;
use App\Services\OutletManagementService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OutletManagementController extends Controller
{
    public function __construct(
        protected OutletManagementService $outletManagementService,
    ) {
    }

    public function index(OutletManagementIndexRequest $request): Response
    {
        $data = $this->outletManagementService->getDashboard(
            $request->user(),
            $request->validated(),
        );

        return Inertia::render('Settings/Outlets', [
            'outlets' => $data['outlets'],
            'summary' => $data['summary'],
            'comparison' => $data['comparison'],
            'filters' => $data['filters'],
            'period' => $data['period'],
            'success' => session('success'),
        ]);
    }

    public function store(StoreOutletRequest $request): RedirectResponse
    {
        $this->outletManagementService->create(
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('settings.outlets.index')
            ->with('success', 'Outlet baru berhasil ditambahkan.');
    }

    public function update(UpdateOutletRequest $request, Outlet $outlet): RedirectResponse
    {
        $this->outletManagementService->update(
            $outlet,
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('settings.outlets.index')
            ->with('success', 'Data outlet berhasil diperbarui.');
    }

    public function updateStatus(UpdateOutletStatusRequest $request, Outlet $outlet): RedirectResponse
    {
        $this->outletManagementService->updateStatus(
            $outlet,
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('settings.outlets.index')
            ->with('success', 'Status outlet berhasil diperbarui.');
    }
}
