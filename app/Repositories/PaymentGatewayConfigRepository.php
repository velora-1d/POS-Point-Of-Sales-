<?php

namespace App\Repositories;

use App\Models\Outlet;
use App\Models\PaymentGatewayConfig;
use Illuminate\Support\Collection;

class PaymentGatewayConfigRepository
{
    public function getOutlets(): Collection
    {
        return Outlet::query()
            ->with('paymentGatewayConfig')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);
    }

    public function findByOutletId(string $outletId): ?PaymentGatewayConfig
    {
        return PaymentGatewayConfig::query()
            ->where('outlet_id', $outletId)
            ->first();
    }

    public function upsertByOutlet(string $outletId, array $payload): PaymentGatewayConfig
    {
        return PaymentGatewayConfig::query()->updateOrCreate(
            ['outlet_id' => $outletId],
            $payload,
        );
    }
}
