<?php

namespace App\Services;

use App\Models\BackupSecuritySetting;
use App\Models\Outlet;
use App\Models\User;
use App\Repositories\BackupSecurityRepository;
use App\Repositories\SecurityActivityLogRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BackupSecurityService
{
    protected const FREQUENCY_OPTIONS = [
        ['value' => 'daily', 'label' => 'Harian', 'description' => 'Backup otomatis setiap hari pada jam yang ditentukan.'],
        ['value' => 'weekly', 'label' => 'Mingguan', 'description' => 'Cocok untuk outlet dengan perubahan data operasional lebih rendah.'],
        ['value' => 'monthly', 'label' => 'Bulanan', 'description' => 'Backup arsip berkala untuk outlet yang jarang berubah.'],
    ];

    protected const CHANNEL_OPTIONS = [
        ['value' => 'local_download', 'label' => 'Download Lokal', 'description' => 'Backup manual diunduh sebagai file JSON dari aplikasi.'],
        ['value' => 'cloud_storage', 'label' => 'Cloud Storage', 'description' => 'Status kesiapan integrasi cloud backup per outlet.'],
        ['value' => 'hybrid', 'label' => 'Hybrid', 'description' => 'Gabungan backup manual dan readiness cloud storage.'],
    ];

    public function __construct(
        protected BackupSecurityRepository $backupSecurityRepository,
        protected SecurityActivityLogRepository $securityActivityLogRepository,
        protected SecurityActivityLogService $securityActivityLogService,
    ) {
    }

    public function getDashboard(User $actor, array $filters = []): array
    {
        $this->assertCanManage($actor);

        $outlets = $this->backupSecurityRepository->getOutlets();
        $selectedOutlet = $this->resolveSelectedOutlet($outlets, $filters['outlet_id'] ?? null);
        $selectedOutletId = $selectedOutlet['id'] ?? null;
        $storedSetting = $selectedOutletId
            ? $this->backupSecurityRepository->findByOutletId($selectedOutletId)
            : null;

        return [
            'outlets' => $outlets->map(function (Outlet $outlet) {
                return [
                    'id' => $outlet->id,
                    'name' => $outlet->name,
                    'is_active' => (bool) $outlet->is_active,
                    'has_config' => (bool) $outlet->backupSecuritySetting,
                    'last_backup_at' => $outlet->backupSecuritySetting?->last_backup_at?->toIso8601String(),
                    'last_backup_status' => $outlet->backupSecuritySetting?->last_backup_status,
                ];
            })->values()->all(),
            'selectedOutlet' => $selectedOutlet,
            'summary' => $this->buildSummary($outlets),
            'formDefaults' => $this->buildFormDefaults($selectedOutletId, $storedSetting),
            'backupOptions' => [
                'frequencies' => self::FREQUENCY_OPTIONS,
                'channels' => self::CHANNEL_OPTIONS,
            ],
            'latestBackup' => $this->buildLatestBackup($storedSetting),
            'securityPosture' => $selectedOutletId
                ? $this->buildSecurityPosture($selectedOutletId, $storedSetting)
                : $this->emptySecurityPosture(),
            'activityLogs' => $selectedOutletId
                ? $this->transformLogs($this->securityActivityLogRepository->getRecentByOutlet($selectedOutletId))
                : [],
            'filters' => [
                'outlet_id' => $selectedOutletId,
            ],
        ];
    }

    public function saveConfig(array $payload, User $actor, ?string $ipAddress = null): string
    {
        $this->assertCanManage($actor);

        $outlet = $this->resolveManagedOutlet((string) $payload['outlet_id']);
        $normalized = [
            'auto_backup_enabled' => (bool) $payload['auto_backup_enabled'],
            'auto_backup_frequency' => (string) $payload['auto_backup_frequency'],
            'auto_backup_time' => sprintf('%s:00', $payload['auto_backup_time']),
            'retention_days' => max(7, min(365, (int) $payload['retention_days'])),
            'backup_channel' => (string) $payload['backup_channel'],
            'encryption_enabled' => (bool) $payload['encryption_enabled'],
            'two_factor_required' => (bool) $payload['two_factor_required'],
            'metadata' => [
                'updated_by' => $actor->id,
                'updated_at' => now()->toIso8601String(),
            ],
        ];

        $this->backupSecurityRepository->upsertByOutlet($outlet->id, $normalized);
        $this->securityActivityLogService->log(
            $actor,
            $outlet->id,
            'backup_security.updated',
            'Konfigurasi backup & keamanan diperbarui.',
            'success',
            [
                'auto_backup_enabled' => $normalized['auto_backup_enabled'],
                'frequency' => $normalized['auto_backup_frequency'],
                'backup_channel' => $normalized['backup_channel'],
                'two_factor_required' => $normalized['two_factor_required'],
            ],
            $ipAddress,
        );

        return $outlet->id;
    }

    public function buildManualBackup(string $outletId, User $actor, ?string $ipAddress = null): array
    {
        $this->assertCanManage($actor);

        $outlet = $this->resolveManagedOutlet($outletId);
        $dataset = $this->backupSecurityRepository->buildBackupDataset($outlet->id);
        $payload = [
            'generated_at' => now()->toIso8601String(),
            'generated_by' => [
                'id' => $actor->id,
                'name' => $actor->name,
                'role' => $actor->role?->type ?? $actor->role?->name,
            ],
            'backup_type' => 'manual_json_export',
            'data' => $dataset,
        ];
        $content = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $filename = 'backup-' . Str::slug($outlet->name) . '-' . now()->format('Ymd-His') . '.json';
        $sizeBytes = strlen((string) $content);

        $this->backupSecurityRepository->upsertByOutlet($outlet->id, [
            'last_backup_status' => 'success',
            'last_backup_at' => now(),
            'last_backup_file_name' => $filename,
            'last_backup_size_bytes' => $sizeBytes,
            'metadata' => [
                'last_manual_backup_by' => $actor->id,
                'last_manual_backup_at' => now()->toIso8601String(),
            ],
        ]);
        $this->securityActivityLogService->log(
            $actor,
            $outlet->id,
            'backup_security.manual_backup',
            'Backup manual berhasil dibuat dan diunduh.',
            'success',
            [
                'file_name' => $filename,
                'size_bytes' => $sizeBytes,
            ],
            $ipAddress,
        );

        return [
            'filename' => $filename,
            'content' => $content ?: '{}',
        ];
    }

    protected function buildSummary(Collection $outlets): array
    {
        return [
            'total_outlets' => $outlets->count(),
            'configured_outlets' => $outlets->filter(fn (Outlet $outlet) => $outlet->backupSecuritySetting !== null)->count(),
            'successful_backups' => $outlets->filter(fn (Outlet $outlet) => $outlet->backupSecuritySetting?->last_backup_status === 'success')->count(),
            'warning_logs' => $this->securityActivityLogRepository->countWarnings(),
        ];
    }

    protected function buildFormDefaults(?string $outletId, ?BackupSecuritySetting $storedSetting): array
    {
        return [
            'outlet_id' => $outletId,
            'auto_backup_enabled' => $storedSetting?->auto_backup_enabled ?? true,
            'auto_backup_frequency' => $storedSetting?->auto_backup_frequency ?? 'daily',
            'auto_backup_time' => substr((string) ($storedSetting?->auto_backup_time ?? '03:00:00'), 0, 5),
            'retention_days' => $storedSetting?->retention_days ?? 30,
            'backup_channel' => $storedSetting?->backup_channel ?? 'hybrid',
            'encryption_enabled' => $storedSetting?->encryption_enabled ?? true,
            'two_factor_required' => $storedSetting?->two_factor_required ?? false,
            'has_config' => $storedSetting !== null,
        ];
    }

    protected function buildLatestBackup(?BackupSecuritySetting $storedSetting): array
    {
        return [
            'status' => $storedSetting?->last_backup_status ?? 'not_started',
            'performed_at' => $storedSetting?->last_backup_at?->toIso8601String(),
            'file_name' => $storedSetting?->last_backup_file_name,
            'size_bytes' => (int) ($storedSetting?->last_backup_size_bytes ?? 0),
            'storage_label' => match ($storedSetting?->backup_channel) {
                'cloud_storage' => 'Cloud storage readiness',
                'local_download' => 'Download lokal',
                default => 'Hybrid readiness',
            },
        ];
    }

    protected function buildSecurityPosture(string $outletId, ?BackupSecuritySetting $storedSetting): array
    {
        $stats = $this->backupSecurityRepository->getUserSecurityStats($outletId);
        $warningCount = $this->securityActivityLogRepository->countWarnings($outletId, 14);

        return [
            'encryption_status' => $storedSetting?->encryption_enabled ?? true,
            'two_factor_status' => $storedSetting?->two_factor_required ?? false,
            'approval_pin_coverage' => [
                'total' => $stats['owner_supervisor_total'],
                'secured' => $stats['owner_supervisor_with_pin'],
            ],
            'active_user_coverage' => [
                'total' => $stats['active_users'],
                'secured' => $stats['users_with_approval_pin'],
            ],
            'recent_warnings' => $warningCount,
        ];
    }

    protected function emptySecurityPosture(): array
    {
        return [
            'encryption_status' => true,
            'two_factor_status' => false,
            'approval_pin_coverage' => [
                'total' => 0,
                'secured' => 0,
            ],
            'active_user_coverage' => [
                'total' => 0,
                'secured' => 0,
            ],
            'recent_warnings' => 0,
        ];
    }

    protected function transformLogs(Collection $logs): array
    {
        return $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'actor_name' => $log->actor_name ?: 'System',
                'actor_role' => $log->actor_role,
                'action' => $log->action,
                'description' => $log->description,
                'ip_address' => $log->ip_address ?: 'Internal',
                'status' => $log->status,
                'created_at' => $log->created_at?->toIso8601String(),
            ];
        })->values()->all();
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
        $outlet = $this->backupSecurityRepository->getOutlets()->firstWhere('id', $outletId);

        if (!$outlet instanceof Outlet) {
            abort(404);
        }

        return $outlet;
    }

    protected function assertCanManage(User $actor): void
    {
        if ($actor->role?->type !== 'owner') {
            abort(403, 'Backup & keamanan data hanya tersedia untuk owner.');
        }
    }
}
