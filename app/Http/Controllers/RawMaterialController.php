<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRawMaterialRequest;
use App\Http\Requests\UpdateRawMaterialRequest;
use App\Http\Requests\UpdateRawMaterialStockRequest;
use App\Models\RawMaterial;
use App\Services\RawMaterialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RawMaterialController extends Controller
{
    public function __construct(
        protected RawMaterialService $rawMaterialService,
    ) {
    }

    public function index(Request $request): Response
    {
        $data = $this->rawMaterialService->getInventoryDashboard(
            $request->user(),
            $request->only(['search', 'status', 'per_page']),
        );

        return Inertia::render('RawMaterials/Index', [
            'materials' => $data['materials'],
            'summary' => $data['summary'],
            'filters' => $data['filters'],
            'success' => session('success'),
        ]);
    }

    public function store(StoreRawMaterialRequest $request): RedirectResponse
    {
        $this->rawMaterialService->create(
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('raw-materials.index')
            ->with('success', 'Bahan baku berhasil ditambahkan.');
    }

    public function update(
        UpdateRawMaterialRequest $request,
        RawMaterial $rawMaterial,
    ): RedirectResponse {
        $this->rawMaterialService->update(
            $rawMaterial,
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('raw-materials.index')
            ->with('success', 'Bahan baku berhasil diperbarui.');
    }

    public function addStock(
        UpdateRawMaterialStockRequest $request,
        RawMaterial $rawMaterial,
    ): RedirectResponse {
        $this->rawMaterialService->addStock(
            $rawMaterial,
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('raw-materials.index')
            ->with('success', 'Stok bahan baku berhasil ditambahkan.');
    }

    public function adjustStock(
        UpdateRawMaterialStockRequest $request,
        RawMaterial $rawMaterial,
    ): RedirectResponse {
        $this->rawMaterialService->adjustStock(
            $rawMaterial,
            (int) $request->validated('quantity'),
            $request->user(),
        );

        return redirect()
            ->route('raw-materials.index')
            ->with('success', 'Adjustment stok bahan baku berhasil disimpan.');
    }
}
