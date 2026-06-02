<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService,
    ) {
    }

    public function index(Request $request): Response
    {
        $data = $this->productService->getProductCatalog(
            $request->user(),
            $request->only(['search', 'category_id', 'per_page']),
        );

        return Inertia::render('Products/Index', [
            'products' => $data['products'],
            'summary' => $data['summary'],
            'categories' => $data['categories'],
            'rawMaterials' => $data['rawMaterials'],
            'filters' => $data['filters'],
            'priceTiers' => [
                ['id' => 'normal', 'name' => 'Harga Normal'],
                ['id' => 'member', 'name' => 'Harga Member'],
                ['id' => 'grosir', 'name' => 'Harga Grosir'],
                ['id' => 'custom', 'name' => 'Harga Khusus/Lainnya'],
            ],
            'success' => session('success'),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->productService->createProduct(
            $request->validated(),
            $request->user()
        );

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    public function update(
        UpdateProductRequest $request,
        Product $product
    ): RedirectResponse {
        $this->productService->updateProduct(
            $product,
            $request->validated(),
            $request->user()
        );

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product, Request $request): RedirectResponse
    {
        $this->productService->deleteProduct($product, $request->user());

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil dihapus.');
    }
}
