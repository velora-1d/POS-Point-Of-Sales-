<?php

namespace App\Services;

use App\Models\Outlet;
use App\Models\Product;
use App\Models\User;
use App\Repositories\ProductHppRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductHppService
{
    public function __construct(
        protected ProductHppRepository $productHppRepository,
    ) {
    }

    public function getHppDashboard(?User $user, array $filters = []): array
    {
        $outletId = $user?->outlet_id ?? Outlet::query()->value('id');

        if (!$outletId) {
            throw ValidationException::withMessages([
                'error' => 'Belum ada outlet terdaftar di sistem.',
            ]);
        }

        $products = $this->productHppRepository->paginateProductsByOutlet($outletId, $filters);
        $products->getCollection()->transform(function (Product $product) {
            $calculatedHpp = $this->productHppRepository->calculateProductHpp($product);
            $basePrice = (float) $product->base_price;

            $product->setAttribute('calculated_hpp', $calculatedHpp);
            $product->setAttribute('gross_margin', $basePrice - $calculatedHpp);

            return $product;
        });

        return [
            'products' => $products,
            'summary' => $this->productHppRepository->getSummary($outletId),
            'rawMaterials' => $this->productHppRepository->getActiveRawMaterials($outletId),
            'filters' => [
                'search' => (string) ($filters['search'] ?? ''),
                'recipe_status' => $filters['recipe_status'] ?? '',
                'per_page' => (int) ($filters['per_page'] ?? 12),
            ],
        ];
    }

    public function updateIngredients(Product $product, array $payload, User $actor): void
    {
        if ($product->outlet_id !== $actor->outlet_id) {
            throw ValidationException::withMessages([
                'error' => 'Produk ini tidak berada di outlet aktif Anda.',
            ]);
        }

        $rawIngredients = collect($payload['ingredients'] ?? []);

        $hasIncompleteRow = $rawIngredients->contains(function (array $ingredient) {
            $hasMaterial = filled($ingredient['raw_material_id'] ?? null);
            $hasQuantity = filled($ingredient['quantity'] ?? null);

            return $hasMaterial xor $hasQuantity;
        });

        if ($hasIncompleteRow) {
            throw ValidationException::withMessages([
                'ingredients' => 'Setiap baris resep harus memilih bahan baku dan quantity sekaligus.',
            ]);
        }

        $normalizedIngredients = $rawIngredients
            ->map(fn (array $ingredient) => [
                'raw_material_id' => $ingredient['raw_material_id'],
                'quantity' => round((float) $ingredient['quantity'], 2),
            ])
            ->filter(fn (array $ingredient) => $ingredient['raw_material_id'] && $ingredient['quantity'] > 0)
            ->values();

        $duplicates = $normalizedIngredients
            ->pluck('raw_material_id')
            ->duplicates();

        if ($duplicates->isNotEmpty()) {
            throw ValidationException::withMessages([
                'ingredients' => 'Satu bahan baku hanya boleh muncul satu kali dalam resep produk.',
            ]);
        }

        $rawMaterialIds = $normalizedIngredients->pluck('raw_material_id')->all();
        $rawMaterials = $this->productHppRepository->findRecipeMaterials($product->outlet_id, $rawMaterialIds);

        if (count($rawMaterialIds) !== $rawMaterials->count()) {
            throw ValidationException::withMessages([
                'ingredients' => 'Ada bahan baku yang tidak aktif atau tidak tersedia di outlet ini.',
            ]);
        }

        DB::transaction(function () use ($product, $normalizedIngredients) {
            $this->productHppRepository->replaceIngredients(
                $product,
                $normalizedIngredients->all(),
            );

            $freshProduct = $product->load(['ingredients.rawMaterial']);
            $calculatedHpp = $this->productHppRepository->calculateProductHpp($freshProduct);

            $this->productHppRepository->updateProductHpp($product, $calculatedHpp);
        });
    }
}
