<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePromoRequest;
use App\Http\Requests\UpdatePromoRequest;
use App\Models\Promo;
use App\Services\PromoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PromoController extends Controller
{
    public function __construct(
        protected PromoService $promoService,
    ) {
    }

    public function index(Request $request): Response
    {
        $data = $this->promoService->getDashboard(
            $request->user(),
            $request->only(['search', 'status', 'type', 'apply_method', 'per_page']),
        );

        return Inertia::render('Promos/Index', [
            'promos' => $data['promos'],
            'summary' => $data['summary'],
            'filters' => $data['filters'],
            'referenceData' => $data['referenceData'],
            'success' => session('success'),
        ]);
    }

    public function store(StorePromoRequest $request): RedirectResponse
    {
        $this->promoService->create(
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('promos.index')
            ->with('success', 'Template promo berhasil ditambahkan.');
    }

    public function update(UpdatePromoRequest $request, Promo $promo): RedirectResponse
    {
        $this->promoService->update(
            $promo,
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('promos.index')
            ->with('success', 'Template promo berhasil diperbarui.');
    }
}
