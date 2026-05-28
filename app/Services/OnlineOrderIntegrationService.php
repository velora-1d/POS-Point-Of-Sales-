<?php

namespace App\Services;

use App\Models\OnlineOrderIntegration;
use App\Models\Outlet;
use App\Models\User;
use App\Repositories\OnlineOrderIntegrationRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

class OnlineOrderIntegrationService
{
    protected const PLATFORMS = [
        'gofood' => [
            'label' => 'GoFood',
            'description' => 'Integrasi order masuk, merchant ID, dan mapping outlet GoFood.',
        ],
        'grabfood' => [
            'label' => 'GrabFood',
            'description' => 'Integrasi order masuk, merchant ID, dan mapping outlet GrabFood.',
        ],
    ];

    public function __construct(
        protected OnlineOrderIntegrationRepository $onlineOrderIntegrationRepository,
        protected SecurityActivityLogService $securityActivityLogService,
    ) {
    }

    public function getDashboard(User $actor): array
    {
        $this->assertCanManage($actor);

        $outlets = $this->onlineOrderIntegrationRepository->getOutlets();
        $platforms = collect(array_keys(self::PLATFORMS))
            ->mapWithKeys(fn (string $platform) => [
                $platform => $this->buildPlatformState($platform, $outlets),
            ])
            ->all();

        return [
            'summary' => [
                'total_outlets' => $outlets->count(),
                'active_integrations' => collect($platforms)->sum(fn (array $platform) => $platform['summary']['active_outlets']),
                'mapped_outlets' => collect($platforms)->sum(fn (array $platform) => $platform['summary']['mapped_outlets']),
            ],
            'platforms' => $platforms,
        ];
    }

    public function savePlatform(string $platform, array $payload, User $actor, ?string $ipAddress = null): void
    {
        $this->assertCanManage($actor);
        $normalizedPlatform = $this->normalizePlatform($platform);
        $mappings = collect($payload['outlet_mappings'] ?? []);

        foreach ($mappings as $mapping) {
            $outletId = (string) $mapping['outlet_id'];
            $existing = $this->onlineOrderIntegrationRepository->findByOutletAndPlatform($outletId, $normalizedPlatform);

            $this->onlineOrderIntegrationRepository->upsertByOutletAndPlatform($outletId, $normalizedPlatform, [
                'is_active' => (bool) $payload['is_active'],
                'environment' => (string) $payload['environment'],
                'merchant_id' => $payload['merchant_id'] ?: null,
                'external_outlet_id' => $mapping['external_outlet_id'] ?: null,
                'api_key_encrypted' => !empty($payload['api_key'])
                    ? Crypt::encryptString((string) $payload['api_key'])
                    : $existing?->api_key_encrypted,
                'api_secret_encrypted' => !empty($payload['api_secret'])
                    ? Crypt::encryptString((string) $payload['api_secret'])
                    : $existing?->api_secret_encrypted,
                'metadata' => [
                    'updated_by' => $actor->id,
                    'updated_at' => now()->toIso8601String(),
                ],
            ]);
        }

        $this->securityActivityLogService->log(
            $actor,
            null,
            'online_integrations.updated',
            'Konfigurasi integrasi ' . strtoupper($normalizedPlatform) . ' diperbarui.',
            'success',
            [
                'platform' => $normalizedPlatform,
                'is_active' => (bool) $payload['is_active'],
                'environment' => $payload['environment'],
                'mapped_outlets' => $mappings->filter(fn (array $mapping) => !empty($mapping['external_outlet_id']))->count(),
            ],
            $ipAddress,
        );
    }

    public function assertWebhookConfigured(string $platform, array $payload): OnlineOrderIntegration
    {
        $normalizedPlatform = $this->normalizePlatform($platform);
        $outletId = (string) $payload['outlet_id'];
        $integration = $this->onlineOrderIntegrationRepository->findByOutletAndPlatform($outletId, $normalizedPlatform);

        if (!$integration) {
            throw ValidationException::withMessages([
                'outlet_id' => 'Integrasi ' . strtoupper($normalizedPlatform) . ' untuk outlet ini belum dikonfigurasi.',
            ]);
        }

        if (!$integration->is_active) {
            throw ValidationException::withMessages([
                'outlet_id' => 'Integrasi ' . strtoupper($normalizedPlatform) . ' untuk outlet ini sedang nonaktif.',
            ]);
        }

        if (!$integration->merchant_id || !$integration->external_outlet_id || !$integration->api_key_encrypted) {
            throw ValidationException::withMessages([
                'outlet_id' => 'Konfigurasi integrasi ' . strtoupper($normalizedPlatform) . ' untuk outlet ini belum lengkap.',
            ]);
        }

        $incomingMerchantId = $payload['merchant_id'] ?? data_get($payload, 'metadata.merchant_id');
        if ($incomingMerchantId && $incomingMerchantId !== $integration->merchant_id) {
            throw ValidationException::withMessages([
                'merchant_id' => 'Merchant ID webhook tidak cocok dengan konfigurasi outlet.',
            ]);
        }

        $incomingExternalOutletId = $payload['external_outlet_id'] ?? data_get($payload, 'metadata.outlet_external_id');
        if ($incomingExternalOutletId && $incomingExternalOutletId !== $integration->external_outlet_id) {
            throw ValidationException::withMessages([
                'external_outlet_id' => 'Mapping outlet dari webhook tidak cocok dengan konfigurasi.',
            ]);
        }

        $this->onlineOrderIntegrationRepository->touchLastSyncedAt($integration->id);

        return $integration;
    }

    protected function buildPlatformState(string $platform, Collection $outlets): array
    {
        $platformConfig = self::PLATFORMS[$platform];
        $integrations = $this->onlineOrderIntegrationRepository
            ->getPlatformIntegrations($platform)
            ->keyBy('outlet_id');
        $sample = $integrations->first();
        $mappings = $outlets->map(function (Outlet $outlet) use ($integrations) {
            $integration = $integrations->get($outlet->id);

            return [
                'outlet_id' => $outlet->id,
                'outlet_name' => $outlet->name,
                'outlet_active' => (bool) $outlet->is_active,
                'external_outlet_id' => $integration?->external_outlet_id ?? '',
                'is_mapped' => filled($integration?->external_outlet_id),
                'integration_active' => (bool) ($integration?->is_active ?? false),
            ];
        })->values()->all();

        return [
            'label' => $platformConfig['label'],
            'description' => $platformConfig['description'],
            'webhook_url' => route('online-orders.webhook.' . $platform),
            'formDefaults' => [
                'is_active' => (bool) ($sample?->is_active ?? false),
                'merchant_id' => $sample?->merchant_id ?? '',
                'environment' => $sample?->environment ?? 'production',
                'api_key' => '',
                'api_secret' => '',
                'has_stored_api_key' => $sample?->api_key_encrypted !== null,
                'has_stored_api_secret' => $sample?->api_secret_encrypted !== null,
                'outlet_mappings' => $mappings,
            ],
            'summary' => [
                'active_outlets' => collect($mappings)->filter(fn (array $mapping) => $mapping['integration_active'])->count(),
                'mapped_outlets' => collect($mappings)->filter(fn (array $mapping) => $mapping['is_mapped'])->count(),
                'last_synced_at' => $integrations->pluck('last_synced_at')->filter()->sortDesc()->first()?->toIso8601String(),
            ],
        ];
    }

    protected function normalizePlatform(string $platform): string
    {
        $normalizedPlatform = strtolower(trim($platform));

        if (!array_key_exists($normalizedPlatform, self::PLATFORMS)) {
            abort(404);
        }

        return $normalizedPlatform;
    }

    protected function assertCanManage(User $actor): void
    {
        if ($actor->role?->type !== 'owner') {
            abort(403, 'Integrasi order online hanya tersedia untuk owner.');
        }
    }
}
