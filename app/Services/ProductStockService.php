<?php

namespace App\Services;

use App\Models\Outlet;
use App\Models\Product;
use App\Models\User;
use App\Services\InventoryExpiryService;
use App\Repositories\ProductStockRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductStockService
{
    public function __construct(
        protected ProductStockRepository $productStockRepository,
        protected InventoryExpiryService $inventoryExpiryService,
    ) {
    }

    public function getStockDashboard(?User $user, array $filters = []): array
    {
        $outletId = $user?->outlet_id ?? Outlet::query()->value('id');

        if (!$outletId) {
            throw ValidationException::withMessages([
                'error' => 'Belum ada outlet terdaftar di sistem.',
            ]);
        }

        $this->ensureStockRows($outletId);

        return [
            'products' => $this->productStockRepository->paginateByOutlet($outletId, $filters),
            'summary' => $this->productStockRepository->getSummary($outletId),
            'filters' => [
                'search' => (string) ($filters['search'] ?? ''),
                'status' => $filters['status'] ?: '',
                'per_page' => (int) ($filters['per_page'] ?? 12),
            ],
        ];
    }

    public function updateStock(Product $product, array $payload, User $actor): void
    {
        if ($product->outlet_id !== $actor->outlet_id) {
            throw ValidationException::withMessages([
                'error' => 'Produk ini tidak berada di outlet aktif Anda.',
            ]);
        }

        if (!$product->track_stock) {
            throw ValidationException::withMessages([
                'error' => 'Produk ini tidak menggunakan tracking stok.',
            ]);
        }

        $this->ensureStockRows($product->outlet_id);

        $stock = $this->productStockRepository->findStockForProduct($product);

        if (!$stock) {
            throw ValidationException::withMessages([
                'error' => 'Data stok produk tidak ditemukan.',
            ]);
        }

        $previousStock = (int) $stock->current_stock;

        DB::transaction(function () use ($product, $stock, $payload, $previousStock) {
            $this->productStockRepository->updateStock($stock, $payload);

            $restockQuantity = (int) $payload['current_stock'] - $previousStock;

            $this->inventoryExpiryService->recordProductRestock(
                $product,
                $restockQuantity,
                $payload,
            );
        });
    }

    protected function ensureStockRows(string $outletId): void
    {
        $products = $this->productStockRepository->getTrackedProductsWithoutStock($outletId);

        if ($products->isEmpty()) {
            return;
        }

        $this->productStockRepository->createStockRows($products);
    }
}
