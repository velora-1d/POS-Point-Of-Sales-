<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Table;
use App\Models\TableReservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TableReservationService
{
    public function create(array $payload, User $actor): TableReservation
    {
        $outletId = $actor->outlet_id;

        if (!$outletId) {
            throw ValidationException::withMessages([
                'error' => 'User belum terhubung ke outlet aktif.',
            ]);
        }

        $table = Table::query()
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->find($payload['table_id']);

        if (!$table) {
            throw ValidationException::withMessages([
                'table_id' => 'Meja tidak ditemukan pada outlet aktif.',
            ]);
        }

        if ($table->status === 'occupied') {
            throw ValidationException::withMessages([
                'table_id' => 'Meja sedang dipakai dan tidak bisa direservasi.',
            ]);
        }

        $hasActiveReservation = TableReservation::query()
            ->where('table_id', $table->id)
            ->where('status', 'booked')
            ->exists();

        if ($hasActiveReservation) {
            throw ValidationException::withMessages([
                'table_id' => 'Meja ini sudah punya reservasi aktif.',
            ]);
        }

        $customer = $this->resolveCustomer(
            $outletId,
            $payload['customer_id'] ?? null,
            $payload['customer_name'],
            $payload['customer_phone'],
        );

        return DB::transaction(function () use ($payload, $actor, $table, $customer) {
            $reservation = TableReservation::query()->create([
                'outlet_id' => $table->outlet_id,
                'table_id' => $table->id,
                'customer_id' => $customer?->id,
                'created_by' => $actor->id,
                'customer_name' => $payload['customer_name'],
                'customer_phone' => $payload['customer_phone'],
                'guest_count' => $payload['guest_count'],
                'reserved_for' => $payload['reserved_for'],
                'status' => 'booked',
                'notes' => $payload['notes'] ?? null,
            ]);

            $this->syncTableStatus($table->id);

            return $reservation->fresh(['table', 'customer', 'creator']);
        });
    }

    public function updateStatus(
        TableReservation $reservation,
        array $payload,
        User $actor,
    ): TableReservation {
        if ($reservation->outlet_id !== $actor->outlet_id) {
            throw ValidationException::withMessages([
                'error' => 'Reservasi ini tidak berada di outlet aktif Anda.',
            ]);
        }

        return DB::transaction(function () use ($reservation, $payload) {
            $reservation->update([
                'status' => $payload['status'],
            ]);

            $this->syncTableStatus($reservation->table_id);

            return $reservation->fresh(['table', 'customer', 'creator']);
        });
    }

    public function completeReservationForOrder(
        ?string $reservationId,
        string $outletId,
        ?string $tableId,
    ): void {
        if (!$reservationId || !$tableId) {
            return;
        }

        TableReservation::query()
            ->whereKey($reservationId)
            ->where('outlet_id', $outletId)
            ->where('table_id', $tableId)
            ->where('status', 'booked')
            ->update([
                'status' => 'completed',
            ]);
    }

    public function syncTableStatus(?string $tableId): void
    {
        if (!$tableId) {
            return;
        }

        $hasActiveOrder = Order::query()
            ->where('table_id', $tableId)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->exists();

        if ($hasActiveOrder) {
            Table::query()
                ->whereKey($tableId)
                ->update(['status' => 'occupied']);

            return;
        }

        $hasActiveReservation = TableReservation::query()
            ->where('table_id', $tableId)
            ->where('status', 'booked')
            ->exists();

        Table::query()
            ->whereKey($tableId)
            ->update(['status' => $hasActiveReservation ? 'reserved' : 'available']);
    }

    protected function resolveCustomer(
        string $outletId,
        ?string $customerId,
        string $customerName,
        string $customerPhone,
    ): ?Customer {
        if ($customerId) {
            return Customer::query()
                ->where('outlet_id', $outletId)
                ->find($customerId);
        }

        $customer = Customer::firstOrNew([
            'outlet_id' => $outletId,
            'phone' => $customerPhone,
        ]);

        $customer->name = $customerName ?: ($customer->name ?: 'Pelanggan Reservasi');
        $customer->is_active = true;
        $customer->registered_via = $customer->exists ? $customer->registered_via : 'reservasi_meja';
        $customer->save();

        return $customer;
    }
}
