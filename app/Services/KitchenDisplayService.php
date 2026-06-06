<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\User;
use App\Repositories\KitchenDisplayRepository;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class KitchenDisplayService
{
    public function __construct(
        protected KitchenDisplayRepository $kitchenDisplayRepository,
        protected OnlineOrderStatusSyncService $onlineOrderStatusSyncService,
    ) {
    }

    public function getBoardData(User $user): array
    {
        if (!$user->outlet_id) {
            return [
                'orders' => [],
                'boardConfig' => $this->getBoardConfig(),
                'success' => session('success'),
                'error' => 'User belum terhubung ke outlet.',
            ];
        }

        $orders = $this->kitchenDisplayRepository
            ->getActiveBoardOrders($user->outlet_id)
            ->map(function (Order $order) {
                return [
                    'id' => $order->id,
                    'orderNumber' => $order->order_number,
                    'tableLabel' => $order->table?->name ?? 'Takeaway',
                    'customerName' => $order->customer?->name,
                    'source' => $order->external_platform ?: $order->source,
                    'status' => $order->status,
                    'notes' => $order->notes,
                    'estimatedMinutes' => $order->estimated_time ?: 15,
                    'pendingStartedAt' => optional($order->pending_started_at)->toIso8601String()
                        ?: optional($order->created_at)->toIso8601String(),
                    'cookingStartedAt' => optional($order->cooking_started_at)->toIso8601String(),
                    'updatedAt' => optional($order->updated_at)->toIso8601String(),
                    'items' => $order->items->map(function ($item) {
                        $variantName = $item->variant?->name ? ' - '.$item->variant->name : '';

                        return [
                            'id' => $item->id,
                            'name' => trim(($item->product?->name ?? 'Menu tidak ditemukan').$variantName),
                            'quantity' => (int) $item->quantity,
                            'categoryId' => $item->product?->category?->id,
                            'categoryName' => $item->product?->category?->name,
                            'notes' => $item->notes,
                        ];
                    })->values(),
                ];
            })
            ->values();

        $settings = \App\Models\NotificationSetting::query()
            ->where('outlet_id', $user->outlet_id)
            ->first();
        $metadata = $settings ? ($settings->metadata ?? []) : [];

        return [
            'orders' => $orders,
            'boardConfig' => array_merge($this->getBoardConfig(), [
                'voiceSettings' => array_merge([
                    'enabled' => true,
                    'volume' => 1.0,
                    'rate' => 0.9,
                    'pitch' => 1.05,
                ], $metadata['kitchen_voice'] ?? [])
            ]),
            'history' => $this->getRecentHistory($user->outlet_id),
            'success' => session('success'),
        ];
    }

    public function getBarBoardData(User $user): array
    {
        if (!$user->outlet_id) {
            return [
                'orders' => [],
                'success' => session('success'),
                'error' => 'User belum terhubung ke outlet.',
            ];
        }

        $orders = $this->kitchenDisplayRepository
            ->getBarBoardOrders($user->outlet_id)
            ->map(function (Order $order) {
                return [
                    'id' => $order->id,
                    'orderNumber' => $order->order_number,
                    'tableLabel' => $order->table?->name ?? 'Takeaway',
                    'customerName' => $order->customer?->name,
                    'source' => $order->external_platform ?: $order->source,
                    'status' => $order->status,
                    'notes' => $order->notes,
                    'estimatedMinutes' => $order->estimated_time ?: 15,
                    'updatedAt' => optional($order->updated_at)->toIso8601String(),
                    'items' => $order->items->map(function ($item) {
                        $variantName = $item->variant?->name ? ' - '.$item->variant->name : '';

                        return [
                            'id' => $item->id,
                            'name' => trim(($item->product?->name ?? 'Menu tidak ditemukan').$variantName),
                            'quantity' => (int) $item->quantity,
                            'categoryId' => $item->product?->category?->id,
                            'categoryName' => $item->product?->category?->name,
                            'notes' => $item->notes,
                        ];
                    })->values(),
                ];
            })
            ->values();

        return [
            'orders' => $orders,
            'history' => $this->getRecentHistory($user->outlet_id),
            'success' => session('success'),
        ];
    }

    public function updateOrderStatus(Order $order, string $action, User $user): void
    {
        if ($order->outlet_id !== $user->outlet_id) {
            throw ValidationException::withMessages([
                'action' => 'Order tidak berada di outlet yang sama dengan user aktif.',
            ]);
        }

        if ($action === 'start_cooking') {
            $this->markAsCooking($order, $user);

            return;
        }

        if ($action === 'finish_cooking') {
            $this->markAsWaitingBar($order, $user);

            return;
        }

        if ($action === 'set_estimate') {
            throw ValidationException::withMessages([
                'action' => 'Gunakan endpoint update estimasi dengan nilai menit yang valid.',
            ]);
        }

        throw ValidationException::withMessages([
            'action' => 'Aksi status kitchen tidak valid.',
        ]);
    }

    public function updateOrderEstimate(Order $order, int $minutes, User $user): void
    {
        if ($order->outlet_id !== $user->outlet_id) {
            throw ValidationException::withMessages([
                'estimate_minutes' => 'Order tidak berada di outlet yang sama dengan user aktif.',
            ]);
        }

        if (in_array($order->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'estimate_minutes' => 'Estimasi tidak bisa diubah untuk order yang sudah selesai atau dibatalkan.',
            ]);
        }

        $order->update([
            'estimated_time' => $minutes,
        ]);
    }

    public function approveBarReady(Order $order, User $user): void
    {
        if ($order->outlet_id !== $user->outlet_id) {
            throw ValidationException::withMessages([
                'order' => 'Order tidak berada di outlet yang sama dengan user aktif.',
            ]);
        }

        if ($order->status !== 'waiting_bar_approval') {
            throw ValidationException::withMessages([
                'order' => 'Hanya order yang menunggu approval bar yang bisa ditandai ready.',
            ]);
        }

        $order->update([
            'status' => 'ready',
        ]);

        $this->logStatusChange(
            $order,
            'waiting_bar_approval',
            'ready',
            $user,
            'Order di-approve bar dan siap disajikan.',
        );

        $this->syncOnlineOrderStatus(
            $order,
            'ready',
            'Status platform dicatat saat bar mengubah order menjadi ready.',
        );

        $this->notifyCustomer(
            $order,
            "Pesanan Siap!",
            "Pesanan Anda di {$order->outlet->name} sudah siap dan akan segera diantar ke meja {$order->table->name}."
        );
    }

    protected function notifyCustomer(Order $order, string $title, string $body): void
    {
        $fcmToken = $order->metadata['customer_fcm_token'] ?? null;
        if ($fcmToken) {
            \App\Services\FirebasePushService::sendPush($fcmToken, $title, $body, [
                'type' => 'order_status_update',
                'order_id' => $order->id,
                'status' => $order->status,
            ]);
        }
    }

    protected function markAsCooking(Order $order, User $actor): void
    {
        if ($order->status !== 'pending') {
            throw ValidationException::withMessages([
                'action' => 'Hanya order pending yang bisa dimulai untuk dimasak.',
            ]);
        }

        $order->update([
            'status' => 'in_progress',
            'pending_started_at' => $order->pending_started_at ?: Carbon::now(),
            'cooking_started_at' => Carbon::now(),
        ]);

        $this->logStatusChange(
            $order,
            'pending',
            'in_progress',
            $actor,
            'Kitchen mulai memproses order.',
        );

        $this->syncOnlineOrderStatus(
            $order,
            'in_progress',
            'Status platform dicatat saat kitchen mulai memproses order.',
        );

        $this->notifyCustomer(
            $order,
            "Sedang Dimasak 🍳",
            "Koki kami sedang mulai memasak pesanan Anda. Mohon ditunggu ya!"
        );
    }

    protected function markAsWaitingBar(Order $order, User $actor): void
    {
        if ($order->status !== 'in_progress') {
            throw ValidationException::withMessages([
                'action' => 'Hanya order in progress yang bisa ditandai selesai.',
            ]);
        }

        $order->update([
            'status' => 'waiting_bar_approval',
        ]);

        $this->logStatusChange(
            $order,
            'in_progress',
            'waiting_bar_approval',
            $actor,
            'Kitchen selesai memasak dan menunggu finalisasi bar.',
        );

        $this->notifyCustomer(
            $order,
            "Tahap Finishing ✨",
            "Makanan Anda sudah selesai dimasak dan sedang dalam tahap penataan akhir (finishing)."
        );

        $this->syncOnlineOrderStatus(
            $order,
            'waiting_bar_approval',
            'Status platform dicatat saat kitchen selesai memasak.',
        );
    }

    protected function getRecentHistory(string $outletId): array
    {
        return $this->kitchenDisplayRepository
            ->getRecentHistoryLogs($outletId)
            ->map(function (OrderStatusLog $log) {
                return [
                    'id' => $log->id,
                    'orderNumber' => $log->order?->order_number,
                    'tableLabel' => $log->order?->table?->name ?? 'Takeaway',
                    'customerName' => $log->order?->customer?->name,
                    'fromStatus' => $log->from_status,
                    'toStatus' => $log->to_status,
                    'changedByName' => $log->changer?->name ?? 'System',
                    'changedByType' => $log->changed_by_type ?? 'system',
                    'notes' => $log->notes,
                    'createdAt' => optional($log->created_at)->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    protected function logStatusChange(
        Order $order,
        ?string $fromStatus,
        string $toStatus,
        ?User $actor,
        ?string $notes = null,
    ): void {
        OrderStatusLog::create([
            'order_id' => $order->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by' => $actor?->id,
            'changed_by_type' => $actor ? 'user' : 'system',
            'notes' => $notes,
            'created_at' => now(),
        ]);
    }

    protected function syncOnlineOrderStatus(Order $order, string $status, string $notes): void
    {
        $this->onlineOrderStatusSyncService->sync($order, $status, $notes);
    }

    protected function getBoardConfig(): array
    {
        return [
            'waitingAlertSeconds' => 180,
            'cookingWarningSeconds' => 120,
            'defaultEstimatedMinutes' => 15,
        ];
    }
}
