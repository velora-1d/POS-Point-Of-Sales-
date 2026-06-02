<?php

namespace App\Services;

use App\Models\PaymentGatewayConfig;
use App\Models\User;
use App\Repositories\PaymentGatewayConfigRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentGatewayConfigService
{
    protected const PROVIDERS = [
        [
            'value' => 'pakasir',
            'label' => 'Pakasir',
            'description' => 'Gateway QRIS yang sudah terhubung ke flow checkout dan webhook project ini.',
        ],
    ];

    protected const GATEWAY_METHODS = [
        [
            'value' => 'qris',
            'label' => 'QRIS',
            'description' => 'Sudah terhubung ke checkout dan webhook runtime.',
        ],
        [
            'value' => 'ewallet',
            'label' => 'E-Wallet',
            'description' => 'Disimpan sebagai readiness outlet, belum dipakai penuh di flow kasir saat ini.',
        ],
        [
            'value' => 'debit',
            'label' => 'Debit / Kartu',
            'description' => 'Disimpan sebagai readiness outlet, belum dipakai penuh di flow kasir saat ini.',
        ],
        [
            'value' => 'transfer',
            'label' => 'Transfer',
            'description' => 'Disimpan sebagai readiness outlet, belum dipakai penuh di flow kasir saat ini.',
        ],
    ];

    public function __construct(
        protected PaymentGatewayConfigRepository $paymentGatewayConfigRepository,
    ) {
    }

    public function getDashboard(User $actor, array $filters = []): array
    {
        $this->assertCanManage($actor);

        $outlets = $this->paymentGatewayConfigRepository->getOutlets();
        $selectedOutlet = $this->resolveSelectedOutlet($outlets, $filters['outlet_id'] ?? null);
        $selectedOutletId = $selectedOutlet['id'] ?? null;
        $storedConfig = $selectedOutletId
            ? $this->paymentGatewayConfigRepository->findByOutletId($selectedOutletId)
            : null;
        $effectiveConfig = $selectedOutletId
            ? $this->buildEffectiveConfig($selectedOutletId, $storedConfig)
            : $this->emptyEffectiveConfig();

        return [
            'outlets' => $outlets->map(function ($outlet) {
                return [
                    'id' => $outlet->id,
                    'name' => $outlet->name,
                    'is_active' => (bool) $outlet->is_active,
                    'has_override' => (bool) $outlet->paymentGatewayConfig,
                    'gateway_active' => (bool) ($outlet->paymentGatewayConfig?->is_active ?? false),
                ];
            })->values()->all(),
            'selectedOutlet' => $selectedOutlet,
            'summary' => $this->buildSummary($outlets),
            'formDefaults' => $this->buildFormDefaults($selectedOutletId, $storedConfig, $effectiveConfig),
            'effectiveConfig' => $effectiveConfig,
            'gatewayOptions' => [
                'providers' => self::PROVIDERS,
                'methods' => self::GATEWAY_METHODS,
            ],
            'filters' => [
                'outlet_id' => $selectedOutletId,
            ],
        ];
    }

    public function saveConfig(array $payload, User $actor): void
    {
        $this->assertCanManage($actor);

        $existingConfig = $this->paymentGatewayConfigRepository->findByOutletId($payload['outlet_id']);
        $normalized = $this->normalizeForPersistence($payload, $existingConfig);

        $this->paymentGatewayConfigRepository->upsertByOutlet($payload['outlet_id'], $normalized);
    }

    public function testConnection(array $payload, User $actor): string
    {
        $this->assertCanManage($actor);

        $existingConfig = $this->paymentGatewayConfigRepository->findByOutletId($payload['outlet_id']);
        $normalized = $this->normalizeForRuntime($payload, $existingConfig, true);

        if (($normalized['provider'] ?? null) !== 'pakasir') {
            throw ValidationException::withMessages([
                'provider' => 'Provider gateway belum didukung untuk test koneksi.',
            ]);
        }

        if (!$normalized['base_url'] || !$normalized['project_slug'] || !$normalized['api_key']) {
            throw ValidationException::withMessages([
                'test_connection' => 'Base URL, project slug, dan API key wajib diisi untuk uji koneksi.',
            ]);
        }

        try {
            $response = Http::acceptJson()
                ->timeout(10)
                ->get(
                    rtrim((string) $normalized['base_url'], '/') . '/api/transactiondetail',
                    [
                        'project' => $normalized['project_slug'],
                        'amount' => 1,
                        'order_id' => 'connection-test-' . Str::lower(Str::random(6)),
                        'api_key' => $normalized['api_key'],
                    ],
                );
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'test_connection' => 'Gateway tidak bisa dijangkau dari server aplikasi.',
            ]);
        }

        if ($response->serverError()) {
            throw ValidationException::withMessages([
                'test_connection' => 'Gateway merespons error server (' . $response->status() . ').',
            ]);
        }

        if ($response->clientError()) {
            return 'Gateway terjangkau. Respons validasi diterima dengan status '
                . $response->status()
                . ', cek API key / project slug jika transaksi nyata nanti gagal.';
        }

        return 'Koneksi ke gateway berhasil. Endpoint Pakasir merespons normal.';
    }

    public function resolvePakasirConfig(?string $outletId = null): array
    {
        if ($outletId) {
            $storedConfig = $this->paymentGatewayConfigRepository->findByOutletId($outletId);

            if ($storedConfig) {
                if (!$storedConfig->is_active) {
                    throw ValidationException::withMessages([
                        'payment_gateway' => 'Gateway payment outlet sedang nonaktif.',
                    ]);
                }

                $resolved = $this->normalizeForRuntime([
                    'provider' => $storedConfig->provider,
                    'is_active' => $storedConfig->is_active,
                    'base_url' => $storedConfig->base_url,
                    'project_slug' => $storedConfig->project_slug,
                    'callback_url' => $storedConfig->callback_url,
                    'active_payment_methods' => $storedConfig->active_payment_methods ?? [],
                ], $storedConfig, false);

                return [
                    ...$resolved,
                    'source' => 'outlet',
                ];
            }
        }

        $envConfig = $this->resolveEnvFallback();

        if ($envConfig['is_ready']) {
            return [
                'provider' => 'pakasir',
                'is_active' => true,
                'base_url' => $envConfig['base_url'],
                'project_slug' => $envConfig['project_slug'],
                'callback_url' => $envConfig['callback_url'],
                'api_key' => $envConfig['api_key'],
                'api_secret' => $envConfig['api_secret'],
                'active_payment_methods' => ['qris'],
                'source' => 'env',
            ];
        }

        throw ValidationException::withMessages([
            'payment_gateway' => 'Konfigurasi Pakasir belum lengkap (Environment Variables MISSING).',
        ]);
    }

    public function assertMethodEnabledForOutlet(?string $outletId, string $method): void
    {
        if ($method === 'cash') {
            return;
        }

        $storedConfig = $outletId
            ? $this->paymentGatewayConfigRepository->findByOutletId($outletId)
            : null;

        if ($storedConfig) {
            $enabledMethods = collect($storedConfig->active_payment_methods ?? [])
                ->map(fn ($value) => strtolower((string) $value))
                ->filter()
                ->values();

            if (!$storedConfig->is_active) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Gateway payment untuk outlet ini sedang nonaktif.',
                ]);
            }

            if (!$enabledMethods->contains($method)) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Metode bayar ' . strtoupper($method) . ' belum diaktifkan untuk outlet ini.',
                ]);
            }

            return;
        }

        if ($method === 'qris' && $this->resolveEnvFallback()['is_ready']) {
            return;
        }

        throw ValidationException::withMessages([
            'payment_method' => 'Konfigurasi gateway untuk metode bayar ini belum siap.',
        ]);
    }

    protected function buildSummary(Collection $outlets): array
    {
        return [
            'total_outlets' => $outlets->count(),
            'override_outlets' => $outlets->filter(fn ($outlet) => $outlet->paymentGatewayConfig !== null)->count(),
            'active_overrides' => $outlets->filter(fn ($outlet) => (bool) ($outlet->paymentGatewayConfig?->is_active ?? false))->count(),
            'qris_ready_outlets' => $outlets->filter(function ($outlet) {
                $methods = collect($outlet->paymentGatewayConfig?->active_payment_methods ?? [])
                    ->map(fn ($value) => strtolower((string) $value))
                    ->filter();

                return (bool) ($outlet->paymentGatewayConfig?->is_active ?? false)
                    && $methods->contains('qris');
            })->count(),
        ];
    }

    protected function buildFormDefaults(
        ?string $outletId,
        ?PaymentGatewayConfig $storedConfig,
        array $effectiveConfig,
    ): array {
        $activeMethods = $storedConfig?->active_payment_methods;

        if (!is_array($activeMethods) || $activeMethods === []) {
            $activeMethods = $effectiveConfig['active_payment_methods'] ?? ['qris'];
        }

        return [
            'outlet_id' => $outletId,
            'provider' => $storedConfig?->provider ?? $effectiveConfig['provider'],
            'is_active' => $storedConfig?->is_active ?? false,
            'base_url' => $storedConfig?->base_url ?? ($effectiveConfig['base_url'] ?? ''),
            'project_slug' => $storedConfig?->project_slug ?? ($effectiveConfig['project_slug'] ?? ''),
            'callback_url' => $storedConfig?->callback_url ?? ($effectiveConfig['callback_url'] ?? route('payments.webhook.pakasir')),
            'api_key' => '',
            'api_secret' => '',
            'active_payment_methods' => $activeMethods,
            'has_stored_api_key' => $storedConfig?->api_key_encrypted !== null,
            'has_stored_api_secret' => $storedConfig?->api_secret_encrypted !== null,
        ];
    }

    protected function buildEffectiveConfig(?string $outletId, ?PaymentGatewayConfig $storedConfig): array
    {
        if ($storedConfig) {
            return [
                'source' => 'outlet',
                'provider' => $storedConfig->provider,
                'is_active' => (bool) $storedConfig->is_active,
                'base_url' => $storedConfig->base_url,
                'project_slug' => $storedConfig->project_slug,
                'callback_url' => $storedConfig->callback_url ?: route('payments.webhook.pakasir'),
                'active_payment_methods' => $storedConfig->active_payment_methods ?? [],
                'has_api_key' => $storedConfig->api_key_encrypted !== null,
                'has_api_secret' => $storedConfig->api_secret_encrypted !== null,
            ];
        }

        if ($outletId) {
            $env = $this->resolveEnvFallback();

            if ($env['is_ready']) {
                return [
                    'source' => 'env',
                    'provider' => 'pakasir',
                    'is_active' => true,
                    'base_url' => $env['base_url'],
                    'project_slug' => $env['project_slug'],
                    'callback_url' => $env['callback_url'],
                    'active_payment_methods' => ['qris'],
                    'has_api_key' => true,
                    'has_api_secret' => $env['api_secret'] !== null,
                ];
            }
        }

        return $this->emptyEffectiveConfig();
    }

    protected function emptyEffectiveConfig(): array
    {
        return [
            'source' => 'missing',
            'provider' => 'pakasir',
            'is_active' => false,
            'base_url' => '',
            'project_slug' => '',
            'callback_url' => route('payments.webhook.pakasir'),
            'active_payment_methods' => [],
            'has_api_key' => false,
            'has_api_secret' => false,
        ];
    }

    protected function resolveSelectedOutlet(Collection $outlets, ?string $requestedOutletId): ?array
    {
        if ($outlets->isEmpty()) {
            return null;
        }

        $selected = $requestedOutletId
            ? $outlets->firstWhere('id', $requestedOutletId)
            : $outlets->first();

        $selected ??= $outlets->first();

        return $selected ? [
            'id' => $selected->id,
            'name' => $selected->name,
            'is_active' => (bool) $selected->is_active,
        ] : null;
    }

    protected function normalizeForPersistence(array $payload, ?PaymentGatewayConfig $existingConfig): array
    {
        $normalized = $this->normalizeForRuntime($payload, $existingConfig, true);

        return [
            'provider' => $normalized['provider'],
            'is_active' => $normalized['is_active'],
            'base_url' => $normalized['base_url'],
            'project_slug' => $normalized['project_slug'],
            'callback_url' => $normalized['callback_url'],
            'api_key_encrypted' => $normalized['api_key'] !== null
                ? Crypt::encryptString($normalized['api_key'])
                : $existingConfig?->api_key_encrypted,
            'api_secret_encrypted' => $normalized['api_secret'] !== null
                ? Crypt::encryptString($normalized['api_secret'])
                : $existingConfig?->api_secret_encrypted,
            'active_payment_methods' => $normalized['active_payment_methods'],
            'metadata' => array_merge($existingConfig?->metadata ?? [], [
                'runtime_ready' => $normalized['is_active'] && collect($normalized['active_payment_methods'])->isNotEmpty(),
            ]),
        ];
    }

    protected function normalizeForRuntime(array $payload, ?PaymentGatewayConfig $existingConfig, bool $strict): array
    {
        $provider = strtolower(trim((string) ($payload['provider'] ?? 'pakasir')));
        $isActive = (bool) ($payload['is_active'] ?? false);
        $baseUrl = $this->nullableTrim($payload['base_url'] ?? $existingConfig?->base_url);
        $projectSlug = $this->nullableTrim($payload['project_slug'] ?? $existingConfig?->project_slug);
        $callbackUrl = $this->nullableTrim($payload['callback_url'] ?? $existingConfig?->callback_url) ?: route('payments.webhook.pakasir');
        $apiKeyInput = $this->nullableTrim($payload['api_key'] ?? null);
        $apiSecretInput = $this->nullableTrim($payload['api_secret'] ?? null);
        $activeMethods = collect($payload['active_payment_methods'] ?? ($existingConfig?->active_payment_methods ?? ['qris']))
            ->map(fn ($value) => strtolower(trim((string) $value)))
            ->filter(fn ($value) => in_array($value, ['qris', 'ewallet', 'debit', 'transfer'], true))
            ->unique()
            ->values()
            ->all();

        $existingApiKey = $existingConfig?->api_key_encrypted
            ? Crypt::decryptString($existingConfig->api_key_encrypted)
            : null;
        $existingApiSecret = $existingConfig?->api_secret_encrypted
            ? Crypt::decryptString($existingConfig->api_secret_encrypted)
            : null;

        $apiKey = $apiKeyInput ?? $existingApiKey;
        $apiSecret = $apiSecretInput ?? $existingApiSecret;

        if ($isActive) {
            if ($provider !== 'pakasir') {
                throw ValidationException::withMessages([
                    'provider' => 'Provider gateway ini belum didukung.',
                ]);
            }

            if (!$baseUrl || !$projectSlug || !$apiKey) {
                throw ValidationException::withMessages([
                    'api_key' => 'Base URL, project slug, dan API key wajib diisi untuk mengaktifkan gateway.',
                ]);
            }

            if ($activeMethods === []) {
                throw ValidationException::withMessages([
                    'active_payment_methods' => 'Pilih minimal satu metode bayar gateway yang aktif.',
                ]);
            }
        }

        if ($strict && $provider === 'pakasir' && $baseUrl && !Str::startsWith($baseUrl, ['http://', 'https://'])) {
            throw ValidationException::withMessages([
                'base_url' => 'Base URL gateway harus valid dan lengkap.',
            ]);
        }

        return [
            'provider' => $provider,
            'is_active' => $isActive,
            'base_url' => $baseUrl,
            'project_slug' => $projectSlug,
            'callback_url' => $callbackUrl,
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'active_payment_methods' => $activeMethods,
        ];
    }

    protected function resolveEnvFallback(): array
    {
        $apiKey = config('services.pakasir.api_key');
        $apiSecret = config('services.pakasir.api_secret');
        $baseUrl = config('services.pakasir.base_url');
        $projectSlug = config('services.pakasir.slug');
        
        $callbackUrl = config('services.pakasir.callback_url');
        if ($callbackUrl && str_contains($callbackUrl, '${APP_URL}')) {
            $callbackUrl = str_replace('${APP_URL}', config('app.url'), $callbackUrl);
        }
        $callbackUrl = $callbackUrl ?: route('payments.webhook.pakasir');

        return [
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'base_url' => $baseUrl,
            'project_slug' => $projectSlug,
            'callback_url' => $callbackUrl,
            'is_ready' => filled($apiKey) && filled($baseUrl) && filled($projectSlug),
        ];
    }

    protected function nullableTrim(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized !== '' ? $normalized : null;
    }

    protected function assertCanManage(User $actor): void
    {
        if ($actor->role?->type !== 'owner') {
            abort(403, 'Konfigurasi payment gateway hanya tersedia untuk owner.');
        }
    }
}
