<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Models\ProductPrice;
use App\Models\ProductVariant;
use App\Models\RawMaterial;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductRepository
{
    public function paginateByOutlet(string $outletId, array $filters = []): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $categoryId = $filters['category_id'] ?? null;
        $perPage = (int) ($filters['per_page'] ?? 12);

        return Product::query()
            ->where('outlet_id', $outletId)
            ->when($categoryId, fn (Builder $query) => $query->where('category_id', $categoryId))
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'ilike', '%' . $search . '%')
                        ->orWhere('description', 'ilike', '%' . $search . '%');
                });
            })
            ->with([
                'category',
                'variants' => fn ($query) => $query->where('is_active', true)->orderBy('name'),
                'prices' => fn ($query) => $query->where('outlet_id', $outletId)->where('is_active', true)->orderBy('tier'),
                'ingredients.rawMaterial',
            ])
            ->withCount([
                'variants as active_variants_count' => fn ($query) => $query->where('is_active', true),
                'prices as active_prices_count' => fn ($query) => $query->where('outlet_id', $outletId)->where('is_active', true),
            ])
            ->latest()
            ->paginate(max(6, min($perPage, 24)))
            ->withQueryString();
    }

    public function getSummary(string $outletId): array
    {
        $baseQuery = Product::query()->where('outlet_id', $outletId);

        return [
            'total' => (clone $baseQuery)->count(),
            'available' => (clone $baseQuery)->where('is_available', true)->where('is_active', true)->count(),
            'withVariants' => (clone $baseQuery)->whereHas('variants', fn (Builder $query) => $query->where('is_active', true))->count(),
            'withMultiPrice' => (clone $baseQuery)->whereHas('prices', fn (Builder $query) => $query->where('outlet_id', $outletId)->where('is_active', true))->count(),
        ];
    }

    public function getCategories(string $outletId)
    {
        return Category::query()
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name']);
    }

    public function getActiveRawMaterials(string $outletId)
    {
        return RawMaterial::query()
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'cost_per_unit']);
    }

    public function create(array $payload): Product
    {
        if (empty($payload['id'])) {
            $payload['id'] = (string) Str::uuid();
        }

        return Product::query()->create($payload);
    }

    public function update(Product $product, array $payload): Product
    {
        $product->update($payload);

        return $product;
    }

    public function syncVariants(Product $product, array $variants): void
    {
        $existingIds = [];

        foreach ($variants as $variantData) {
            $id = $variantData['id'] ?? (string) Str::uuid();
            $existingIds[] = $id;

            ProductVariant::query()->updateOrCreate(
                ['id' => $id],
                [
                    'product_id' => $product->id,
                    'name' => $variantData['name'],
                    'additional_price' => $variantData['additional_price'],
                    'is_active' => $variantData['is_active'],
                ]
            );
        }

        ProductVariant::query()
            ->where('product_id', $product->id)
            ->whereNotIn('id', $existingIds)
            ->delete();
    }

    public function syncPrices(Product $product, array $prices): void
    {
        $existingIds = [];

        foreach ($prices as $priceData) {
            $id = $priceData['id'] ?? (string) Str::uuid();
            $existingIds[] = $id;

            ProductPrice::query()->updateOrCreate(
                ['id' => $id],
                [
                    'product_id' => $product->id,
                    'outlet_id' => $product->outlet_id,
                    'tier' => $priceData['tier'],
                    'tier_label' => $priceData['tier_label'] ?? null,
                    'price' => $priceData['price'],
                    'happy_hour_start' => $priceData['happy_hour_start'] ?? null,
                    'happy_hour_end' => $priceData['happy_hour_end'] ?? null,
                    'is_active' => $priceData['is_active'],
                ]
            );
        }

        ProductPrice::query()
            ->where('product_id', $product->id)
            ->whereNotIn('id', $existingIds)
            ->delete();
    }

    public function syncIngredients(Product $product, array $ingredients): void
    {
        ProductIngredient::query()
            ->where('product_id', $product->id)
            ->delete();

        foreach ($ingredients as $ingredientData) {
            ProductIngredient::query()->create([
                'product_id' => $product->id,
                'raw_material_id' => $ingredientData['raw_material_id'],
                'quantity' => $ingredientData['quantity'],
            ]);
        }
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }
}
