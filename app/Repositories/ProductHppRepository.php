<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\ProductIngredient;
use App\Models\RawMaterial;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProductHppRepository
{
    public function paginateProductsByOutlet(string $outletId, array $filters = []): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $recipeStatus = $filters['recipe_status'] ?? null;
        $perPage = (int) ($filters['per_page'] ?? 12);

        return Product::query()
            ->where('outlet_id', $outletId)
            ->with([
                'category',
                'ingredients.rawMaterial',
            ])
            ->withCount('ingredients')
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'ilike', '%' . $search . '%')
                        ->orWhere('description', 'ilike', '%' . $search . '%');
                });
            })
            ->when($recipeStatus === 'with_recipe', fn (Builder $query) => $query->has('ingredients'))
            ->when($recipeStatus === 'without_recipe', fn (Builder $query) => $query->doesntHave('ingredients'))
            ->latest()
            ->paginate(max(6, min($perPage, 24)))
            ->withQueryString();
    }

    public function getSummary(string $outletId): array
    {
        $baseQuery = Product::query()->where('outlet_id', $outletId);

        $products = Product::query()
            ->where('outlet_id', $outletId)
            ->with('ingredients.rawMaterial')
            ->get();

        $withRecipe = $products->filter(fn (Product $product) => $product->ingredients->isNotEmpty());
        $synced = $withRecipe->filter(function (Product $product) {
            $calculatedHpp = $this->calculateProductHpp($product);

            return abs(((float) $product->hpp) - $calculatedHpp) < 0.01;
        });
        $healthyMargin = $products->filter(function (Product $product) {
            return (float) $product->base_price > $this->calculateProductHpp($product);
        });

        return [
            'total' => (clone $baseQuery)->count(),
            'with_recipe' => $withRecipe->count(),
            'synced_hpp' => $synced->count(),
            'healthy_margin' => $healthyMargin->count(),
        ];
    }

    public function getActiveRawMaterials(string $outletId): Collection
    {
        return RawMaterial::query()
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'unit',
                'cost_per_unit',
                'quantity',
                'minimum_stock',
            ]);
    }

    public function findRecipeMaterials(string $outletId, array $rawMaterialIds): Collection
    {
        return RawMaterial::query()
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->whereIn('id', $rawMaterialIds)
            ->get();
    }

    public function replaceIngredients(Product $product, array $ingredients): void
    {
        ProductIngredient::query()
            ->where('product_id', $product->id)
            ->delete();

        foreach ($ingredients as $index => $ingredient) {
            ProductIngredient::query()->create([
                'product_id' => $product->id,
                'raw_material_id' => $ingredient['raw_material_id'],
                'quantity' => $ingredient['quantity'],
            ]);
        }
    }

    public function updateProductHpp(Product $product, float $hpp): void
    {
        $product->update([
            'hpp' => $hpp,
        ]);
    }

    public function calculateProductHpp(Product $product): float
    {
        return (float) $product->ingredients->sum(function (ProductIngredient $ingredient) {
            return ((float) $ingredient->quantity) * ((float) ($ingredient->rawMaterial?->cost_per_unit ?? 0));
        });
    }
}
