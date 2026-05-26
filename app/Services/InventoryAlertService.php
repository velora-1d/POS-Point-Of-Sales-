<?php

namespace App\Services;

use App\Models\Outlet;
use App\Models\User;
use App\Repositories\InventoryAlertRepository;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class InventoryAlertService
{
    public function __construct(
        protected InventoryAlertRepository $inventoryAlertRepository,
    ) {
    }

    public function getLowStockSnapshot(?User $user, int $limit = 12): array
    {
        $outletId = $user?->outlet_id ?? Outlet::query()->value('id');

        if (!$outletId) {
            throw ValidationException::withMessages([
                'error' => 'Belum ada outlet terdaftar di sistem.',
            ]);
        }

        $productAlerts = $this->inventoryAlertRepository->getLowProductAlerts($outletId);
        $rawMaterialAlerts = $this->inventoryAlertRepository->getLowRawMaterialAlerts($outletId);

        return [
            'summary' => [
                'total' => $productAlerts->count() + $rawMaterialAlerts->count(),
                'products' => $productAlerts->count(),
                'raw_materials' => $rawMaterialAlerts->count(),
                'critical' => $productAlerts->where('severity', 2)->count()
                    + $rawMaterialAlerts->where('severity', 2)->count(),
            ],
            'items' => $this->sortAlerts(
                $productAlerts->concat($rawMaterialAlerts)
            )->take($limit)->values(),
            'productItems' => $this->sortAlerts($productAlerts)->values(),
            'rawMaterialItems' => $this->sortAlerts($rawMaterialAlerts)->values(),
        ];
    }

    protected function sortAlerts(Collection $alerts): Collection
    {
        return $alerts
            ->sortBy([
                ['severity', 'desc'],
                ['current_stock', 'asc'],
                ['name', 'asc'],
            ])
            ->values();
    }
}
