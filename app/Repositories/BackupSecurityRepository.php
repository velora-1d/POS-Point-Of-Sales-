<?php

namespace App\Repositories;

use App\Models\ApprovalRule;
use App\Models\BackupSecuritySetting;
use App\Models\Customer;
use App\Models\NotificationSetting;
use App\Models\OnlineOrderIntegration;
use App\Models\Order;
use App\Models\Outlet;
use App\Models\PaymentGatewayConfig;
use App\Models\PrinterConfig;
use App\Models\Product;
use App\Models\Promo;
use App\Models\Table;
use App\Models\TableQrConfig;
use App\Models\User;
use Illuminate\Support\Collection;

class BackupSecurityRepository
{
    public function getOutlets(): Collection
    {
        return Outlet::query()
            ->with('backupSecuritySetting')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);
    }

    public function findByOutletId(string $outletId): ?BackupSecuritySetting
    {
        return BackupSecuritySetting::query()
            ->where('outlet_id', $outletId)
            ->first();
    }

    public function upsertByOutlet(string $outletId, array $payload): BackupSecuritySetting
    {
        return BackupSecuritySetting::query()->updateOrCreate(
            ['outlet_id' => $outletId],
            $payload,
        );
    }

    public function getUserSecurityStats(string $outletId): array
    {
        $activeUsers = User::query()
            ->where('outlet_id', $outletId)
            ->where('is_active', true);

        $ownerAndSupervisors = User::query()
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->whereHas('role', function ($query) {
                $query->whereIn('type', ['owner', 'supervisor']);
            });

        return [
            'active_users' => (clone $activeUsers)->count(),
            'users_with_approval_pin' => (clone $activeUsers)->whereNotNull('approval_pin')->count(),
            'owner_supervisor_total' => (clone $ownerAndSupervisors)->count(),
            'owner_supervisor_with_pin' => (clone $ownerAndSupervisors)->whereNotNull('approval_pin')->count(),
        ];
    }

    public function buildBackupDataset(string $outletId): array
    {
        $outlet = Outlet::query()->findOrFail($outletId);

        return [
            'outlet' => $outlet->toArray(),
            'users' => User::query()
                ->where('outlet_id', $outletId)
                ->with('role:id,name,type')
                ->orderBy('name')
                ->get()
                ->toArray(),
            'products' => Product::query()
                ->where('outlet_id', $outletId)
                ->with([
                    'variants' => fn ($query) => $query->orderBy('name'),
                    'prices' => fn ($query) => $query->orderByDesc('created_at'),
                ])
                ->orderBy('name')
                ->get()
                ->toArray(),
            'tables' => Table::query()
                ->where('outlet_id', $outletId)
                ->orderBy('name')
                ->get()
                ->toArray(),
            'customers' => Customer::query()
                ->where('outlet_id', $outletId)
                ->orderBy('name')
                ->get()
                ->toArray(),
            'orders' => Order::query()
                ->where('outlet_id', $outletId)
                ->with([
                    'items.product:id,name',
                    'items.variant:id,name',
                    'customer:id,name,phone,email',
                    'table:id,name',
                    'cashier:id,name,email',
                ])
                ->orderBy('created_at')
                ->get()
                ->toArray(),
            'promos' => Promo::query()
                ->where('outlet_id', $outletId)
                ->with('rules')
                ->orderBy('name')
                ->get()
                ->toArray(),
            'configs' => [
                'payment_gateway' => PaymentGatewayConfig::query()
                    ->where('outlet_id', $outletId)
                    ->first()?->toArray(),
                'printer' => PrinterConfig::query()
                    ->where('outlet_id', $outletId)
                    ->first()?->toArray(),
                'table_qr' => TableQrConfig::query()
                    ->where('outlet_id', $outletId)
                    ->first()?->toArray(),
                'notifications' => NotificationSetting::query()
                    ->where('outlet_id', $outletId)
                    ->first()?->toArray(),
                'backup_security' => BackupSecuritySetting::query()
                    ->where('outlet_id', $outletId)
                    ->first()?->toArray(),
                'approval_rules' => ApprovalRule::query()
                    ->where('outlet_id', $outletId)
                    ->first()?->toArray(),
                'online_integrations' => OnlineOrderIntegration::query()
                    ->where('outlet_id', $outletId)
                    ->orderBy('platform')
                    ->get()
                    ->toArray(),
            ],
        ];
    }
}
