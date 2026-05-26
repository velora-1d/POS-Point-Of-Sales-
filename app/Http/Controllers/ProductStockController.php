<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProductStockRequest;
use App\Models\Product;
use App\Services\ProductStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductStockController extends Controller
{
    public function __construct(
        protected ProductStockService $productStockService,
    ) {
    }

    public function index(Request $request): Response
    {
        $data = $this->productStockService->getStockDashboard(
            $request->user(),
            $request->only(['search', 'status', 'per_page']),
        );

        return Inertia::render('Products/Stock', [
            'products' => $data['products'],
            'summary' => $data['summary'],
            'filters' => $data['filters'],
            'success' => session('success'),
        ]);
    }

    public function update(
        UpdateProductStockRequest $request,
        Product $product,
    ): RedirectResponse {
        $this->productStockService->updateStock(
            $product,
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('products.stock')
            ->with('success', 'Stok produk berhasil diperbarui.');
    }
}
