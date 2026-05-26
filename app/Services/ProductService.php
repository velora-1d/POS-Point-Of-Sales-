<?php

namespace App\Services;

use App\Models\Outlet;
use App\Models\User;
use App\Repositories\ProductRepository;
use Illuminate\Validation\ValidationException;

class ProductService
{
    public function __construct(
        protected ProductRepository $productRepository,
    ) {
    }

    public function getProductCatalog(?User $user, array $filters = []): array
    {
        $outletId = $user?->outlet_id ?? Outlet::query()->value('id');

        if (!$outletId) {
            throw ValidationException::withMessages([
                'error' => 'Belum ada outlet terdaftar di sistem.',
            ]);
        }

        return [
            'products' => $this->productRepository->paginateByOutlet($outletId, $filters),
            'summary' => $this->productRepository->getSummary($outletId),
            'categories' => $this->productRepository->getCategories($outletId),
            'filters' => [
                'search' => (string) ($filters['search'] ?? ''),
                'category_id' => $filters['category_id'] ?: null,
                'per_page' => (int) ($filters['per_page'] ?? 12),
            ],
        ];
    }
}
