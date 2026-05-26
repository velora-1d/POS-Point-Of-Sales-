<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
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
            'filters' => $data['filters'],
        ]);
    }
}
