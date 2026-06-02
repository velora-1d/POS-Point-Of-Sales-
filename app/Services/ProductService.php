<?php

namespace App\Services;

use App\Models\Outlet;
use App\Models\Product;
use App\Models\User;
use App\Repositories\ProductRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
            'rawMaterials' => $this->productRepository->getActiveRawMaterials($outletId),
            'filters' => [
                'search' => (string) ($filters['search'] ?? ''),
                'category_id' => $filters['category_id'] ?? null,
                'per_page' => (int) ($filters['per_page'] ?? 12),
            ],
        ];
    }

    public function createProduct(array $payload, User $actor): void
    {
        $outletId = $actor->outlet_id ?? Outlet::query()->value('id');

        if (!$outletId) {
            throw ValidationException::withMessages([
                'error' => 'Gagal menentukan outlet. Pastikan Anda terhubung ke outlet aktif.',
            ]);
        }

        DB::transaction(function () use (&$payload, $actor, $outletId) {
            if (isset($payload['image']) && $payload['image'] instanceof UploadedFile) {
                $path = $payload['image']->store('products', $this->getDisk());
                $payload['image_url'] = Storage::disk($this->getDisk())->url($path);
            }

            $product = $this->productRepository->create(
                $this->normalizePayload($payload, $outletId)
            );

            if (!empty($payload['variants'])) {
                $this->productRepository->syncVariants($product, $payload['variants']);
            }

            if (!empty($payload['prices'])) {
                $this->productRepository->syncPrices($product, $payload['prices']);
            }

            if (!empty($payload['ingredients'])) {
                $this->productRepository->syncIngredients($product, $payload['ingredients']);
            }
        });
    }

    public function updateProduct(Product $product, array $payload, User $actor): void
    {
        $this->assertSameOutlet($product, $actor);

        $outletId = $product->outlet_id;

        DB::transaction(function () use ($product, &$payload, $outletId) {
            if (isset($payload['image']) && $payload['image'] instanceof UploadedFile) {
                // Hapus gambar lama jika ada
                if ($product->image_url) {
                    $this->deleteFile($product->image_url);
                }

                $path = $payload['image']->store('products', $this->getDisk());
                $payload['image_url'] = Storage::disk($this->getDisk())->url($path);
            } else {
                // Pertahankan gambar lama jika tidak upload gambar baru
                $payload['image_url'] = $product->image_url;
            }

            $this->productRepository->update(
                $product,
                $this->normalizePayload($payload, $outletId)
            );

            $this->productRepository->syncVariants($product, $payload['variants'] ?? []);
            $this->productRepository->syncPrices($product, $payload['prices'] ?? []);
            $this->productRepository->syncIngredients($product, $payload['ingredients'] ?? []);
        });
    }

    public function deleteProduct(Product $product, User $actor): void
    {
        $this->assertSameOutlet($product, $actor);

        if ($product->image_url) {
            $this->deleteFile($product->image_url);
        }

        $this->productRepository->delete($product);
    }

    protected function assertSameOutlet(Product $product, User $actor): void
    {
        if ($product->outlet_id !== $actor->outlet_id) {
            throw ValidationException::withMessages([
                'error' => 'Produk ini tidak berada di outlet aktif Anda.',
            ]);
        }
    }

    protected function normalizePayload(array $payload, string $outletId): array
    {
        return [
            'outlet_id' => $outletId,
            'category_id' => $payload['category_id'],
            'name' => trim((string) $payload['name']),
            'description' => trim((string) ($payload['description'] ?? '')),
            'image_url' => $payload['image_url'] ?? null,
            'base_price' => $payload['base_price'],
            'hpp' => $payload['hpp'] ?? 0,
            'is_available' => (bool) $payload['is_available'],
            'is_active' => (bool) $payload['is_active'],
            'track_stock' => (bool) $payload['track_stock'],
            'track_expired' => (bool) $payload['track_expired'],
            'expired_action' => $payload['expired_action'] ?? 'notify_only',
            'expired_reminder_days' => $payload['expired_reminder_days'] ?? [7, 3, 1],
            'sort_order' => (int) ($payload['sort_order'] ?? 0),
        ];
    }

    protected function getDisk(): string
    {
        $config = config('filesystems.disks.r2');
        
        if (!empty($config['key']) && !empty($config['bucket'])) {
            return 'r2';
        }

        return 'public';
    }

    protected function deleteFile(?string $url): void
    {
        if (!$url) return;

        $disk = $this->getDisk();
        $baseUrl = Storage::disk($disk)->url('');
        
        // Bersihkan URL untuk mendapatkan path relatif
        $path = str_replace($baseUrl, '', $url);
        $path = ltrim($path, '/');

        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }
}
