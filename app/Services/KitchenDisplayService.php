<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Repositories\KitchenDisplayRepository;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class KitchenDisplayService
{
    public function __construct(
        protected KitchenDisplayRepository $kitchenDisplayRepository
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
                            'notes' => $item->notes,
                        ];
                    })->values(),
                ];
            })
            ->values();

        return [
            'orders' => $orders,
            'boardConfig' => $this->getBoardConfig(),
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
            $this->markAsCooking($order);

            return;
        }

        if ($action === 'finish_cooking') {
            $this->markAsWaitingBar($order);

            return;
        }

        throw ValidationException::withMessages([
            'action' => 'Aksi status kitchen tidak valid.',
        ]);
    }

    protected function markAsCooking(Order $order): void
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
    }

    protected function markAsWaitingBar(Order $order): void
    {
        if ($order->status !== 'in_progress') {
            throw ValidationException::withMessages([
                'action' => 'Hanya order in progress yang bisa ditandai selesai.',
            ]);
        }

        $order->update([
            'status' => 'waiting_bar_approval',
        ]);
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
