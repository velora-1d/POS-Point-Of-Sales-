<?php

namespace App\Services;

use App\Models\Order;

class OnlineOrderStatusSyncService
{
    public function sync(Order $order, string $internalStatus, ?string $notes = null): void
    {
        if (!in_array($order->source, ['gofood', 'grabfood', 'shopeefood', 'maximfood'], true)) {
            return;
        }

        $metadata = $order->metadata ?? [];
        $history = $metadata['online_sync']['history'] ?? [];
        $entry = [
            'internal_status' => $internalStatus,
            'platform_status' => $this->mapPlatformStatus($internalStatus),
            'synced_at' => now()->toIso8601String(),
            'platform' => $order->external_platform ?: $order->source,
            'transport' => 'stub',
            'notes' => $notes,
        ];

        $history[] = $entry;
        $history = array_slice($history, -10);

        $metadata['online_sync'] = [
            'latest' => $entry,
            'history' => $history,
        ];

        $order->update([
            'metadata' => $metadata,
        ]);
    }

    protected function mapPlatformStatus(string $internalStatus): string
    {
        return match ($internalStatus) {
            'pending' => 'accepted',
            'in_progress' => 'preparing',
            'waiting_bar_approval' => 'almost_ready',
            'ready' => 'ready_for_pickup',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            default => 'accepted',
        };
    }
}
