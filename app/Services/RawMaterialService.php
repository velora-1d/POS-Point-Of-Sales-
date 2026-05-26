<?php

namespace App\Services;

use App\Models\Outlet;
use App\Models\RawMaterial;
use App\Models\User;
use App\Services\InventoryExpiryService;
use App\Repositories\RawMaterialRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RawMaterialService
{
    public function __construct(
        protected RawMaterialRepository $rawMaterialRepository,
        protected InventoryExpiryService $inventoryExpiryService,
    ) {
    }

    public function getInventoryDashboard(?User $user, array $filters = []): array
    {
        $outletId = $user?->outlet_id ?? Outlet::query()->value('id');

        if (!$outletId) {
            throw ValidationException::withMessages([
                'error' => 'Belum ada outlet terdaftar di sistem.',
            ]);
        }

        return [
            'materials' => $this->rawMaterialRepository->paginateByOutlet($outletId, $filters),
            'summary' => $this->rawMaterialRepository->getSummary($outletId),
            'filters' => [
                'search' => (string) ($filters['search'] ?? ''),
                'status' => $filters['status'] ?: '',
                'per_page' => (int) ($filters['per_page'] ?? 12),
            ],
        ];
    }

    public function create(array $payload, User $actor): void
    {
        DB::transaction(function () use ($payload, $actor) {
            $this->rawMaterialRepository->create(
                $this->normalizePayload($payload, $actor->outlet_id)
            );
        });
    }

    public function update(RawMaterial $rawMaterial, array $payload, User $actor): void
    {
        $this->assertSameOutlet($rawMaterial, $actor);

        DB::transaction(function () use ($rawMaterial, $payload) {
            $normalizedPayload = $this->normalizePayload(
                $payload,
                $rawMaterial->outlet_id,
                $rawMaterial
            );

            $this->rawMaterialRepository->update(
                $rawMaterial,
                $normalizedPayload
            );
        });
    }

    public function addStock(RawMaterial $rawMaterial, array $payload, User $actor): void
    {
        $this->assertSameOutlet($rawMaterial, $actor);

        DB::transaction(function () use ($rawMaterial, $payload) {
            $quantity = (int) $payload['quantity'];

            $this->rawMaterialRepository->addStock($rawMaterial, $quantity);
            $this->inventoryExpiryService->recordRawMaterialRestock(
                $rawMaterial,
                $quantity,
                $payload,
            );
        });
    }

    public function adjustStock(RawMaterial $rawMaterial, int $quantity, User $actor): void
    {
        $this->assertSameOutlet($rawMaterial, $actor);

        DB::transaction(function () use ($rawMaterial, $quantity) {
            $this->rawMaterialRepository->adjustStock($rawMaterial, $quantity);
        });
    }

    protected function assertSameOutlet(RawMaterial $rawMaterial, User $actor): void
    {
        if ($rawMaterial->outlet_id !== $actor->outlet_id) {
            throw ValidationException::withMessages([
                'error' => 'Bahan baku ini tidak berada di outlet aktif Anda.',
            ]);
        }
    }

    protected function normalizePayload(
        array $payload,
        string $outletId,
        ?RawMaterial $existingRawMaterial = null,
    ): array
    {
        $trackExpired = (bool) ($payload['track_expired'] ?? false);
        $expiredAction = $trackExpired
            ? ($payload['expired_action'] ?: 'notify_only')
            : null;
        $expiredReminderDays = $trackExpired
            ? array_values($payload['expired_reminder_days'] ?? [7, 3, 1])
            : [];
        $quantity = (int) $payload['quantity'];

        $lastRestockedAt = $existingRawMaterial?->last_restocked_at;

        if (!$existingRawMaterial) {
            $lastRestockedAt = $quantity > 0 ? now() : null;
        } elseif ($quantity > (int) $existingRawMaterial->quantity) {
            $lastRestockedAt = now();
        }

        return [
            'outlet_id' => $outletId,
            'name' => trim((string) $payload['name']),
            'unit' => trim((string) $payload['unit']),
            'quantity' => $quantity,
            'minimum_stock' => (int) $payload['minimum_stock'],
            'cost_per_unit' => $payload['cost_per_unit'],
            'track_expired' => $trackExpired,
            'expired_action' => $expiredAction,
            'expired_reminder_days' => $expiredReminderDays,
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'last_restocked_at' => $lastRestockedAt,
        ];
    }
}
