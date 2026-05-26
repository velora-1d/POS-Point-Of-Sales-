<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProductIngredientsRequest;
use App\Models\Product;
use App\Services\ProductHppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductHppController extends Controller
{
    public function __construct(
        protected ProductHppService $productHppService,
    ) {
    }

    public function index(Request $request): Response
    {
        $data = $this->productHppService->getHppDashboard(
            $request->user(),
            $request->only(['search', 'recipe_status', 'per_page']),
        );

        return Inertia::render('Products/Hpp', [
            'products' => $data['products'],
            'summary' => $data['summary'],
            'rawMaterials' => $data['rawMaterials'],
            'filters' => $data['filters'],
            'success' => session('success'),
        ]);
    }

    public function updateIngredients(
        UpdateProductIngredientsRequest $request,
        Product $product,
    ): RedirectResponse {
        $this->productHppService->updateIngredients(
            $product,
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('products.hpp')
            ->with('success', 'Resep produk dan HPP berhasil diperbarui.');
    }
}
