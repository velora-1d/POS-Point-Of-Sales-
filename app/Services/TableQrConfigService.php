<?php

namespace App\Services;

use App\Models\Outlet;
use App\Models\Table;
use App\Models\TableQrConfig;
use App\Models\User;
use App\Repositories\TableQrConfigRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TableQrConfigService
{
    protected const QR_TEMPLATES = [
        [
            'value' => 'classic_sharp',
            'label' => 'Classic Sharp',
            'description' => 'Kotak tegas untuk print cepat dan kontras tinggi.',
        ],
        [
            'value' => 'modern_rounded',
            'label' => 'Modern Rounded',
            'description' => 'Visual lebih halus untuk materi print dan display premium.',
        ],
        [
            'value' => 'branded_center',
            'label' => 'Branded Center',
            'description' => 'Menyisakan area branding di tengah untuk identitas outlet.',
        ],
    ];

    protected const COLOR_PRESETS = [
        '#111827',
        '#EA580C',
        '#1D4ED8',
        '#047857',
    ];

    public function __construct(
        protected TableQrConfigRepository $tableQrConfigRepository,
    ) {
    }

    public function getDashboard(User $actor, array $filters = []): array
    {
        $this->assertCanManage($actor);

        $outlets = $this->tableQrConfigRepository->getOutlets();
        $selectedOutlet = $this->resolveSelectedOutlet($outlets, $filters['outlet_id'] ?? null);
        $selectedOutletId = $selectedOutlet['id'] ?? null;

        if ($selectedOutletId) {
            $this->ensureOutletTablesReady($selectedOutletId);
        }

        $storedConfig = $selectedOutletId
            ? $this->tableQrConfigRepository->findByOutletId($selectedOutletId)
            : null;
        $sampleTable = $selectedOutletId
            ? $this->tableQrConfigRepository->getActiveTables($selectedOutletId)->first()
            : null;

        return [
            'outlets' => $outlets->map(function (Outlet $outlet) {
                return [
                    'id' => $outlet->id,
                    'name' => $outlet->name,
                    'is_active' => (bool) $outlet->is_active,
                    'has_config' => (bool) $outlet->tableQrConfig,
                    'active_tables_count' => (int) ($outlet->active_tables_count ?? 0),
                ];
            })->values()->all(),
            'selectedOutlet' => $selectedOutlet,
            'summary' => $this->buildSummary($outlets),
            'formDefaults' => $this->buildFormDefaults($selectedOutletId, $selectedOutlet, $storedConfig),
            'preview' => $this->buildPreviewPayload($selectedOutlet, $storedConfig, $sampleTable),
            'regeneration' => $this->buildRegenerationPayload($selectedOutletId, $storedConfig),
            'qrOptions' => [
                'templates' => self::QR_TEMPLATES,
                'colors' => self::COLOR_PRESETS,
            ],
            'filters' => [
                'outlet_id' => $selectedOutletId,
            ],
        ];
    }

    public function saveConfig(array $payload, User $actor): string
    {
        $this->assertCanManage($actor);

        $outlet = $this->resolveManagedOutlet((string) $payload['outlet_id']);
        $this->ensureUniqueStoreSlug((string) $payload['store_slug'], $outlet->id);
        $this->ensureOutletTablesReady($outlet->id);

        $this->tableQrConfigRepository->upsertByOutlet($outlet->id, [
            'store_slug' => (string) $payload['store_slug'],
            'qr_template' => (string) $payload['qr_template'],
            'primary_color' => strtoupper((string) $payload['primary_color']),
            'metadata' => null,
        ]);

        return $outlet->id;
    }

    public function bulkRegenerate(array $payload, User $actor): array
    {
        $this->assertCanManage($actor);

        $outlet = $this->resolveManagedOutlet((string) $payload['outlet_id']);
        $config = $this->tableQrConfigRepository->findByOutletId($outlet->id);

        if (!$config) {
            $defaultSlug = $this->generateUniqueStoreSlug($outlet->name, $outlet->id);

            $config = $this->tableQrConfigRepository->upsertByOutlet($outlet->id, [
                'store_slug' => $defaultSlug,
                'qr_template' => 'classic_sharp',
                'primary_color' => '#111827',
                'metadata' => null,
            ]);
        }

        $tables = $this->tableQrConfigRepository->getActiveTables($outlet->id);
        $count = 0;

        DB::transaction(function () use ($tables, $config, &$count) {
            foreach ($tables as $table) {
                $this->ensureTableIdentity($table);
                $table->forceFill([
                    'qr_code' => $this->generateTableCode($table),
                ])->save();
                $count++;
            }

            $config->forceFill([
                'bulk_regenerated_at' => now(),
            ])->save();
        });

        return [
            'outlet_id' => $outlet->id,
            'count' => $count,
        ];
    }

    public function buildPublicMenuUrlForTable(Table $table, ?TableQrConfig $config = null): ?string
    {
        $this->ensureTableIdentity($table);

        $config ??= $this->tableQrConfigRepository->findByOutletId($table->outlet_id);

        if ($config && $table->qr_code) {
            return route('self-service.menu.alias', [
                'storeSlug' => $config->store_slug,
                'tableCode' => $table->qr_code,
            ]);
        }

        if (!$table->qr_session_token) {
            return null;
        }

        return route('self-service.menu', $table->qr_session_token);
    }

    public function getConfigForOutlet(string $outletId): ?TableQrConfig
    {
        return $this->tableQrConfigRepository->findByOutletId($outletId);
    }

    public function resolveTableByAlias(string $storeSlug, string $tableCode): Table
    {
        $config = $this->tableQrConfigRepository->findByStoreSlug($storeSlug);

        if (!$config) {
            abort(404);
        }

        $table = $this->tableQrConfigRepository->findActiveTableByCode($config->outlet_id, $tableCode);

        if (!$table) {
            abort(404);
        }

        $this->ensureTableIdentity($table);

        return $table->fresh(['outlet']) ?? $table->load('outlet');
    }

    public function ensureOutletTablesReady(string $outletId): void
    {
        $tables = $this->tableQrConfigRepository->getActiveTables($outletId);

        foreach ($tables as $table) {
            $this->ensureTableIdentity($table);
        }
    }

    protected function buildSummary(Collection $outlets): array
    {
        return [
            'total_outlets' => $outlets->count(),
            'configured_outlets' => $outlets->filter(fn (Outlet $outlet) => $outlet->tableQrConfig !== null)->count(),
            'active_tables' => (int) $outlets->sum(fn (Outlet $outlet) => (int) ($outlet->active_tables_count ?? 0)),
            'public_qr_ready' => $outlets->filter(function (Outlet $outlet) {
                return $outlet->tableQrConfig !== null && (int) ($outlet->active_tables_count ?? 0) > 0;
            })->count(),
        ];
    }

    protected function buildFormDefaults(
        ?string $outletId,
        ?array $selectedOutlet,
        ?TableQrConfig $storedConfig,
    ): array {
        $defaultSlug = $storedConfig?->store_slug
            ?? $this->generateUniqueStoreSlug((string) ($selectedOutlet['name'] ?? 'outlet'), $outletId);

        return [
            'outlet_id' => $outletId,
            'base_url' => rtrim(config('app.url'), '/') . '/m',
            'store_slug' => $defaultSlug,
            'qr_template' => $storedConfig?->qr_template ?? 'classic_sharp',
            'primary_color' => strtoupper((string) ($storedConfig?->primary_color ?? '#111827')),
            'has_config' => $storedConfig !== null,
        ];
    }

    protected function buildPreviewPayload(
        ?array $selectedOutlet,
        ?TableQrConfig $storedConfig,
        ?Table $sampleTable,
    ): array {
        $sampleTableCode = $sampleTable?->qr_code ?: 'table-01-demo';
        $storeSlug = $storedConfig?->store_slug
            ?? $this->generateUniqueStoreSlug((string) ($selectedOutlet['name'] ?? 'outlet'), $selectedOutlet['id'] ?? null);
        $sampleUrl = rtrim(config('app.url'), '/') . '/m/' . $storeSlug . '/' . $sampleTableCode;

        return [
            'sample_table_name' => $sampleTable?->name ?? 'Meja 01',
            'sample_table_code' => $sampleTableCode,
            'sample_url' => $sampleUrl,
            'qr_template' => $storedConfig?->qr_template ?? 'classic_sharp',
            'primary_color' => strtoupper((string) ($storedConfig?->primary_color ?? '#111827')),
        ];
    }

    protected function buildRegenerationPayload(?string $outletId, ?TableQrConfig $storedConfig): array
    {
        $activeTables = $outletId
            ? $this->tableQrConfigRepository->getActiveTables($outletId)
            : collect();

        return [
            'active_tables_count' => $activeTables->count(),
            'ready_qr_count' => $activeTables->filter(fn (Table $table) => !empty($table->qr_code))->count(),
            'last_regenerated_at' => $storedConfig?->bulk_regenerated_at?->toIso8601String(),
        ];
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
        $outlet = $this->tableQrConfigRepository->getOutlets()->firstWhere('id', $outletId);

        if (!$outlet instanceof Outlet) {
            abort(404);
        }

        return $outlet;
    }

    protected function ensureUniqueStoreSlug(string $storeSlug, string $outletId): void
    {
        $existing = $this->tableQrConfigRepository->findByStoreSlug($storeSlug);

        if ($existing && $existing->outlet_id !== $outletId) {
            throw ValidationException::withMessages([
                'store_slug' => 'Store slug ini sudah dipakai outlet lain.',
            ]);
        }
    }

    protected function generateUniqueStoreSlug(string $name, ?string $currentOutletId = null): string
    {
        $base = Str::slug($name) ?: 'outlet';
        $candidate = $base;
        $suffix = 1;

        while (true) {
            $existing = $this->tableQrConfigRepository->findByStoreSlug($candidate);

            if (!$existing || $existing->outlet_id === $currentOutletId) {
                return $candidate;
            }

            $suffix++;
            $candidate = $base . '-' . $suffix;
        }
    }

    protected function ensureTableIdentity(Table $table): void
    {
        if ($table->qr_session_token && $table->qr_code) {
            return;
        }

        $table->forceFill([
            'qr_session_token' => $table->qr_session_token ?: (string) Str::ulid(),
            'qr_code' => $table->qr_code ?: $this->generateTableCode($table),
        ])->save();
    }

    protected function generateTableCode(Table $table): string
    {
        $base = Str::slug($table->name) ?: 'table';
        $suffix = strtolower(Str::random(4));

        return $base . '-' . $suffix;
    }

    protected function assertCanManage(User $actor): void
    {
        if ($actor->role?->type !== 'owner') {
            abort(403, 'Konfigurasi QR meja hanya tersedia untuk owner.');
        }
    }
}
