<?php

namespace App\Services;

use App\Models\NotificationSetting;
use App\Models\Order;
use App\Models\Table;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TableManagementService
{
    /**
     * Bersihkan meja secara manual oleh kasir.
     * Cancel semua order aktif, reset status meja ke available.
     */
    public function clearTable(Table $table, User $actor): void
    {
        DB::transaction(function () use ($table, $actor) {
            // Cancel semua order aktif di meja ini
            Order::query()
                ->where('table_id', $table->id)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->update([
                    'status' => 'cancelled',
                    'metadata' => DB::raw("JSON_SET(COALESCE(metadata, '{}'), '$.cleared_by', '{$actor->id}', '$.cleared_at', NOW())"),
                ]);

            // Reset meja ke kosong
            $table->update([
                'status'         => 'available',
                'occupied_at'    => null,
                'current_guests' => 0,
            ]);
        });
    }

    /**
     * Sinkronisasi current_guests berdasarkan semua order aktif di meja.
     * Dipanggil setiap kali ada order masuk atau selesai.
     */
    public function syncTableGuests(string $tableId): void
    {
        $totalGuests = Order::query()
            ->where('table_id', $tableId)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->sum('guests_count');

        Table::query()
            ->whereKey($tableId)
            ->update(['current_guests' => (int) $totalGuests]);
    }

    /**
     * Set occupied_at jika belum di-set (pertama kali meja terpakai).
     */
    public function markOccupiedAt(string $tableId): void
    {
        Table::query()
            ->whereKey($tableId)
            ->whereNull('occupied_at')
            ->update(['occupied_at' => now()]);
    }

    /**
     * Hitung status timer meja: 'ok', 'warning', atau 'danger'.
     *
     * @param  Table                    $table
     * @param  NotificationSetting|null $settings
     * @return 'ok'|'warning'|'danger'
     */
    public function getTableTimerStatus(Table $table, ?NotificationSetting $settings): string
    {
        if (!$table->occupied_at) {
            return 'ok';
        }

        if (!$settings || !$settings->table_duration_alert_enabled) {
            return 'ok';
        }

        $minutesOccupied = (int) now()->diffInMinutes($table->occupied_at);
        $dangerThreshold  = $settings->table_duration_danger_minutes  ?? 180;
        $warningThreshold = $settings->table_duration_warning_minutes ?? 90;

        if ($minutesOccupied >= $dangerThreshold) {
            return 'danger';
        }

        if ($minutesOccupied >= $warningThreshold) {
            return 'warning';
        }

        return 'ok';
    }

    /**
     * Ambil konfigurasi threshold alert untuk outlet tertentu.
     */
    public function getAlertSettings(string $outletId): array
    {
        $setting = NotificationSetting::query()
            ->where('outlet_id', $outletId)
            ->first();

        return [
            'enabled'          => $setting?->table_duration_alert_enabled ?? true,
            'warning_minutes'  => $setting?->table_duration_warning_minutes ?? 90,
            'danger_minutes'   => $setting?->table_duration_danger_minutes ?? 180,
        ];
    }
}
