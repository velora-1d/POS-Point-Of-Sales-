<?php

namespace App\Services;

use App\Models\NotificationSetting;
use App\Models\Outlet;
use App\Models\User;
use App\Repositories\InventoryAlertRepository;
use App\Repositories\NotificationSettingRepository;
use Illuminate\Support\Collection;

class NotificationSettingService
{
    protected const CHANNEL_OPTIONS = [
        [
            'value' => 'in_app',
            'label' => 'In-app',
            'description' => 'Masuk ke dashboard internal dan panel operasional aplikasi.',
        ],
        [
            'value' => 'whatsapp',
            'label' => 'WhatsApp',
            'description' => 'Disiapkan untuk pengiriman ke nomor operasional outlet saat channel aktif tersedia.',
        ],
        [
            'value' => 'email',
            'label' => 'Email',
            'description' => 'Disiapkan untuk ringkasan email ke alamat yang terdaftar di outlet/tim.',
        ],
    ];

    public function __construct(
        protected NotificationSettingRepository $notificationSettingRepository,
        protected InventoryAlertRepository $inventoryAlertRepository,
    ) {
    }

    public function getDashboard(User $actor, array $filters = []): array
    {
        $this->assertCanManage($actor);

        $outlets = $this->notificationSettingRepository->getOutlets();
        $selectedOutlet = $this->resolveSelectedOutlet($outlets, $filters['outlet_id'] ?? null);
        $selectedOutletId = $selectedOutlet['id'] ?? null;
        $storedSetting = $selectedOutletId
            ? $this->notificationSettingRepository->findByOutletId($selectedOutletId)
            : null;
        $defaults = $this->buildFormDefaults($selectedOutletId, $storedSetting);

        return [
            'outlets' => $outlets->map(function (Outlet $outlet) {
                return [
                    'id' => $outlet->id,
                    'name' => $outlet->name,
                    'is_active' => (bool) $outlet->is_active,
                    'has_config' => (bool) $outlet->notificationSetting,
                ];
            })->values()->all(),
            'selectedOutlet' => $selectedOutlet,
            'summary' => $this->buildSummary($outlets),
            'formDefaults' => $defaults,
            'alertOptions' => [
                'channels' => self::CHANNEL_OPTIONS,
            ],
            'snapshots' => $selectedOutletId
                ? $this->buildSnapshots($selectedOutletId, $defaults['kasbon_due_threshold_days'])
                : $this->emptySnapshots(),
            'filters' => [
                'outlet_id' => $selectedOutletId,
            ],
        ];
    }

    public function saveConfig(array $payload, User $actor): string
    {
        $this->assertCanManage($actor);

        $outlet = $this->resolveManagedOutlet((string) $payload['outlet_id']);
        $normalized = $this->normalizePayload($payload);

        $this->notificationSettingRepository->upsertByOutlet($outlet->id, $normalized);

        return $outlet->id;
    }

    protected function buildSummary(Collection $outlets): array
    {
        $configuredOutlets = $outlets->filter(fn (Outlet $outlet) => $outlet->notificationSetting !== null);

        return [
            'total_outlets' => $outlets->count(),
            'configured_outlets' => $configuredOutlets->count(),
            'whatsapp_enabled' => $configuredOutlets->filter(function (Outlet $outlet) {
                $setting = $outlet->notificationSetting;
                $channels = collect([
                    ...($setting?->low_stock_channels ?? []),
                    ...($setting?->kasbon_due_channels ?? []),
                    ...($setting?->online_order_channels ?? []),
                ])->unique();

                return $channels->contains('whatsapp');
            })->count(),
            'email_enabled' => $configuredOutlets->filter(function (Outlet $outlet) {
                $setting = $outlet->notificationSetting;
                $channels = collect([
                    ...($setting?->low_stock_channels ?? []),
                    ...($setting?->kasbon_due_channels ?? []),
                    ...($setting?->online_order_channels ?? []),
                ])->unique();

                return $channels->contains('email');
            })->count(),
        ];
    }

    protected function buildFormDefaults(?string $outletId, ?NotificationSetting $storedSetting): array
    {
        return [
            'outlet_id' => $outletId,
            'low_stock_enabled' => $storedSetting?->low_stock_enabled ?? true,
            'low_stock_channels' => $this->normalizeChannels($storedSetting?->low_stock_channels ?? ['in_app', 'email']),
            'kasbon_due_enabled' => $storedSetting?->kasbon_due_enabled ?? true,
            'kasbon_due_channels' => $this->normalizeChannels($storedSetting?->kasbon_due_channels ?? ['in_app', 'whatsapp']),
            'kasbon_due_threshold_days' => $storedSetting?->kasbon_due_threshold_days ?? 3,
            'online_order_enabled' => $storedSetting?->online_order_enabled ?? true,
            'online_order_channels' => $this->normalizeChannels($storedSetting?->online_order_channels ?? ['in_app']),
            'table_duration_alert_enabled' => $storedSetting?->table_duration_alert_enabled ?? true,
            'table_duration_warning_minutes' => $storedSetting?->table_duration_warning_minutes ?? 90,
            'table_duration_danger_minutes' => $storedSetting?->table_duration_danger_minutes ?? 180,
            'metadata' => $storedSetting?->metadata ?? [
                'kitchen_voice' => [
                    'enabled' => true,
                    'volume' => 1.0,
                    'rate' => 0.9,
                    'pitch' => 1.05,
                ]
            ],
            'has_config' => $storedSetting !== null,
        ];
    }

    protected function buildSnapshots(string $outletId, int $kasbonThresholdDays): array
    {
        $lowProductAlerts = $this->inventoryAlertRepository->getLowProductAlerts($outletId);
        $lowRawMaterialAlerts = $this->inventoryAlertRepository->getLowRawMaterialAlerts($outletId);
        $kasbonSnapshot = $this->notificationSettingRepository->getOverdueKasbonSnapshot($outletId, $kasbonThresholdDays);
        $onlineOrderSnapshot = $this->notificationSettingRepository->getOnlineOrderAlertSnapshot($outletId);

        return [
            'low_stock' => [
                'count' => $lowProductAlerts->count() + $lowRawMaterialAlerts->count(),
                'critical' => $lowProductAlerts->where('severity', 2)->count() + $lowRawMaterialAlerts->where('severity', 2)->count(),
                'items' => $lowProductAlerts
                    ->concat($lowRawMaterialAlerts)
                    ->sortBy([
                        ['severity', 'desc'],
                        ['current_stock', 'asc'],
                        ['name', 'asc'],
                    ])
                    ->take(5)
                    ->values()
                    ->all(),
            ],
            'kasbon_due' => $kasbonSnapshot,
            'online_order' => $onlineOrderSnapshot,
        ];
    }

    protected function emptySnapshots(): array
    {
        return [
            'low_stock' => [
                'count' => 0,
                'critical' => 0,
                'items' => [],
            ],
            'kasbon_due' => [
                'count' => 0,
                'total_outstanding' => 0,
                'items' => [],
            ],
            'online_order' => [
                'count' => 0,
                'today_orders' => 0,
                'items' => [],
            ],
        ];
    }

    protected function normalizePayload(array $payload): array
    {
        return [
            'low_stock_enabled' => (bool) ($payload['low_stock_enabled'] ?? false),
            'low_stock_channels' => $this->normalizeChannels($payload['low_stock_channels'] ?? []),
            'kasbon_due_enabled' => (bool) ($payload['kasbon_due_enabled'] ?? false),
            'kasbon_due_channels' => $this->normalizeChannels($payload['kasbon_due_channels'] ?? []),
            'kasbon_due_threshold_days' => max(1, min(30, (int) ($payload['kasbon_due_threshold_days'] ?? 3))),
            'online_order_enabled' => (bool) ($payload['online_order_enabled'] ?? false),
            'online_order_channels' => $this->normalizeChannels($payload['online_order_channels'] ?? []),
            'table_duration_alert_enabled' => (bool) ($payload['table_duration_alert_enabled'] ?? false),
            'table_duration_warning_minutes' => max(1, (int) ($payload['table_duration_warning_minutes'] ?? 90)),
            'table_duration_danger_minutes' => max(2, (int) ($payload['table_duration_danger_minutes'] ?? 180)),
            'metadata' => $payload['metadata'] ?? null,
        ];
    }

    protected function normalizeChannels(array $channels): array
    {
        return collect($channels)
            ->map(fn ($value) => (string) $value)
            ->filter(fn ($value) => in_array($value, ['in_app', 'whatsapp', 'email'], true))
            ->unique()
            ->values()
            ->all();
    }

    protected function resolveSelectedOutlet(Collection $outlets, ?string $requestedOutletId): ?array
    {
        if ($outlets->isEmpty()) {
            return null;
        }

        $selectedOutlet = $requestedOutletId
            ? $outlets->firstWhere('id', $requestedOutletId)
            : $outlets->first();

        if (!$selectedOutlet instanceof Outlet) {
            return null;
        }

        return [
            'id' => $selectedOutlet->id,
            'name' => $selectedOutlet->name,
            'is_active' => (bool) $selectedOutlet->is_active,
        ];
    }

    protected function resolveManagedOutlet(string $outletId): Outlet
    {
        $outlet = $this->notificationSettingRepository->getOutlets()->firstWhere('id', $outletId);

        if (!$outlet instanceof Outlet) {
            abort(404);
        }

        return $outlet;
    }

    protected function assertCanManage(User $actor): void
    {
        if ($actor->role?->type !== 'owner') {
            abort(403, 'Notifikasi & alert hanya tersedia untuk owner.');
        }
    }
}
