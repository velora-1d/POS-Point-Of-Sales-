<?php

namespace App\Services;

use App\Models\InventoryExpiry;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\User;
use App\Repositories\InventoryExpiryRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryExpiryService
{
    public function __construct(
        protected InventoryExpiryRepository $inventoryExpiryRepository,
    ) {
    }

    public function getReminderSnapshot(?User $user, array $filters = [], int $limit = 8): array
    {
        $outletId = $user?->outlet_id ?? Outlet::query()->value('id');

        if (!$outletId) {
            throw ValidationException::withMessages([
                'error' => 'Belum ada outlet terdaftar di sistem.',
            ]);
        }

        $days = max(1, min((int) ($filters['days'] ?? 7), 30));
        $type = (string) ($filters['type'] ?? 'all');
        $status = (string) ($filters['status'] ?? 'all');

        $this->applyAutoDeactivate($outletId);

        $items = $this->mapExpiries(
            $this->inventoryExpiryRepository->getActiveByOutlet($outletId, $type)
        );

        $filteredItems = $this->filterItems($items, $status, $days);

        return [
            'summary' => $this->buildSummary($items, $days),
            'items' => $filteredItems->take($limit)->values(),
            'upcomingItems' => $items->where('status', 'upcoming')->where('days_left', '<=', $days)->values(),
            'todayItems' => $items->where('status', 'today')->values(),
            'expiredItems' => $items->where('status', 'expired')->values(),
            'filters' => [
                'days' => $days,
                'type' => $type,
                'status' => $status,
            ],
        ];
    }

    public function recordProductRestock(Product $product, int $quantity, array $payload): void
    {
        if (!$product->track_expired || $quantity <= 0) {
            return;
        }

        $expiredDate = $payload['expired_date'] ?? null;

        if (!$expiredDate) {
            throw ValidationException::withMessages([
                'expired_date' => 'Tanggal expired wajib diisi saat restock produk dengan tracking expired.',
            ]);
        }

        $this->inventoryExpiryRepository->create([
            'outlet_id' => $product->outlet_id,
            'trackable_type' => 'product',
            'trackable_id' => $product->id,
            'quantity' => $quantity,
            'batch_code' => $payload['batch_code'] ?: null,
            'expired_at' => $expiredDate,
            'reminder_days' => $product->expired_reminder_days ?: [7, 3, 1],
            'expired_action' => $product->expired_action ?: 'notify_only',
            'is_resolved' => false,
        ]);
    }

    public function recordRawMaterialRestock(RawMaterial $rawMaterial, int $quantity, array $payload): void
    {
        if (!$rawMaterial->track_expired || $quantity <= 0) {
            return;
        }

        $expiredDate = $payload['expired_date'] ?? null;

        if (!$expiredDate) {
            throw ValidationException::withMessages([
                'expired_date' => 'Tanggal expired wajib diisi saat tambah stok bahan baku dengan tracking expired.',
            ]);
        }

        $this->inventoryExpiryRepository->create([
            'outlet_id' => $rawMaterial->outlet_id,
            'trackable_type' => 'raw_material',
            'trackable_id' => $rawMaterial->id,
            'quantity' => $quantity,
            'batch_code' => $payload['batch_code'] ?: null,
            'expired_at' => $expiredDate,
            'reminder_days' => $rawMaterial->expired_reminder_days ?: [7, 3, 1],
            'expired_action' => $rawMaterial->expired_action ?: 'notify_only',
            'is_resolved' => false,
        ]);
    }

    public function handle(InventoryExpiry $inventoryExpiry, array $payload, User $actor): void
    {
        if ($inventoryExpiry->outlet_id !== $actor->outlet_id) {
            throw ValidationException::withMessages([
                'error' => 'Data expired ini tidak berada di outlet aktif Anda.',
            ]);
        }

        DB::transaction(function () use ($inventoryExpiry, $payload) {
            $this->applyManualAction($inventoryExpiry, $payload['action']);

            $this->inventoryExpiryRepository->markResolved($inventoryExpiry, [
                'is_resolved' => true,
                'resolved_action' => $payload['action'],
                'resolved_notes' => $payload['notes'] ?: null,
                'resolved_at' => now(),
            ]);
        });
    }

    protected function applyAutoDeactivate(string $outletId): void
    {
        $this->inventoryExpiryRepository
            ->getAutoDeactivateCandidates($outletId)
            ->each(fn (InventoryExpiry $expiry) => $this->deactivateTrackable($expiry));
    }

    protected function deactivateTrackable(InventoryExpiry $inventoryExpiry): void
    {
        if ($inventoryExpiry->trackable_type === 'product' && $inventoryExpiry->product?->is_available) {
            $inventoryExpiry->product->update([
                'is_available' => false,
            ]);
        }

        if ($inventoryExpiry->trackable_type === 'raw_material' && $inventoryExpiry->rawMaterial?->is_active) {
            $inventoryExpiry->rawMaterial->update([
                'is_active' => false,
            ]);
        }
    }

    protected function applyManualAction(InventoryExpiry $inventoryExpiry, string $action): void
    {
        if ($action === 'deactivate') {
            $this->deactivateTrackable($inventoryExpiry);
        }
    }

    protected function mapExpiries(Collection $expiries): Collection
    {
        return $expiries
            ->map(function (InventoryExpiry $expiry) {
                $expiredAt = Carbon::parse($expiry->expired_at)->startOfDay();
                $daysLeft = Carbon::today()->diffInDays($expiredAt, false);
                $entity = $expiry->trackable_type === 'product'
                    ? $expiry->product
                    : $expiry->rawMaterial;

                if (!$entity) {
                    return null;
                }

                return [
                    'id' => $expiry->id,
                    'trackable_type' => $expiry->trackable_type,
                    'name' => $entity->name,
                    'context' => $expiry->trackable_type === 'product'
                        ? ($expiry->product?->category?->name ?: 'Produk jadi')
                        : 'Bahan baku',
                    'quantity' => $expiry->quantity,
                    'batch_code' => $expiry->batch_code,
                    'expired_at' => $expiredAt->toDateString(),
                    'days_left' => $daysLeft,
                    'status' => $daysLeft < 0 ? 'expired' : ($daysLeft === 0 ? 'today' : 'upcoming'),
                    'reminder_days' => $expiry->reminder_days ?: [7, 3, 1],
                    'reminder_hit' => in_array($daysLeft, $expiry->reminder_days ?: [7, 3, 1], true),
                    'expired_action' => $expiry->expired_action ?: 'notify_only',
                    'route' => $expiry->trackable_type === 'product' ? 'products.stock' : 'raw-materials.index',
                ];
            })
            ->filter()
            ->sortBy([
                ['days_left', 'asc'],
                ['name', 'asc'],
            ])
            ->values();
    }

    protected function filterItems(Collection $items, string $status, int $days): Collection
    {
        return $items
            ->filter(function (array $item) use ($status, $days) {
                if ($status === 'upcoming') {
                    return $item['status'] === 'upcoming' && $item['days_left'] <= $days;
                }

                if ($status === 'today') {
                    return $item['status'] === 'today';
                }

                if ($status === 'expired') {
                    return $item['status'] === 'expired';
                }

                return $item['status'] !== 'upcoming' || $item['days_left'] <= $days;
            })
            ->values();
    }

    protected function buildSummary(Collection $items, int $days): array
    {
        return [
            'upcoming' => $items->where('status', 'upcoming')->where('days_left', '<=', $days)->count(),
            'today' => $items->where('status', 'today')->count(),
            'expired' => $items->where('status', 'expired')->count(),
            'critical' => $items->filter(fn (array $item) => in_array($item['status'], ['today', 'expired'], true))->count(),
        ];
    }
}
