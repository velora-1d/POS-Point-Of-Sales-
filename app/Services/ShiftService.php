<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\User;
use App\Repositories\ShiftRepository;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ShiftService
{
    public function __construct(
        protected ShiftRepository $shiftRepository,
    ) {
    }

    public function getDashboard(User $actor, array $filters = []): array
    {
        $this->assertCanRead($actor);

        $scopeOutletId = $this->resolveScopeOutletId($actor, $filters['outlet_id'] ?? null);
        $resolvedFilters = [
            'status' => $filters['status'] ?? '',
            'user_id' => $filters['user_id'] ?? '',
            'outlet_id' => $scopeOutletId ?? '',
            'start_date' => $filters['start_date'] ?? CarbonImmutable::today()->startOfMonth()->toDateString(),
            'end_date' => $filters['end_date'] ?? CarbonImmutable::today()->toDateString(),
            'per_page' => (int) ($filters['per_page'] ?? 10),
        ];
        $activeShift = $scopeOutletId ? $this->shiftRepository->findActiveShiftForOutlet($scopeOutletId) : null;
        $lastClosedShift = $scopeOutletId ? $this->shiftRepository->findLastClosedShift($scopeOutletId) : null;
        $todaySchedule = $actor->outlet_id
            ? $this->shiftRepository->getTodayScheduleForUser($actor->id, CarbonImmutable::today())
            : null;

        return [
            'activeShift' => $activeShift ? array_merge(
                $this->transformShift($activeShift, true),
                ['can_close' => $actor->role?->type === 'owner' || $actor->role?->type === 'supervisor' || $activeShift->user_id === $actor->id],
            ) : null,
            'lastClosedShift' => $lastClosedShift ? $this->transformShift($lastClosedShift, false) : null,
            'todaySchedule' => $todaySchedule ? [
                'id' => $todaySchedule->id,
                'shift_template_id' => $todaySchedule->shift_template_id,
                'shift_template_name' => $todaySchedule->shiftTemplate?->name,
                'start_time' => $todaySchedule->shiftTemplate?->start_time,
                'end_time' => $todaySchedule->shiftTemplate?->end_time,
            ] : null,
            'shiftTemplates' => $this->shiftRepository->getShiftTemplates($scopeOutletId),
            'cashRecap' => $this->shiftRepository->getCashRecap($resolvedFilters, $scopeOutletId),
            'history' => $this->transformShiftPaginator(
                $this->shiftRepository->paginate($resolvedFilters, $scopeOutletId),
            ),
            'filters' => $resolvedFilters,
            'referenceData' => [
                'outlets' => $this->shiftRepository->getOutlets(
                    $actor->role?->type === 'owner' ? null : $scopeOutletId,
                ),
                'cashiers' => $this->shiftRepository->getCashierUsers($scopeOutletId),
            ],
            'canManage' => in_array($actor->role?->type, ['owner', 'kasir', 'supervisor'], true),
            'canRead' => in_array($actor->role?->type, ['owner', 'kasir', 'supervisor'], true),
        ];
    }

    public function open(array $payload, User $actor): Shift
    {
        $this->assertCanManage($actor);
        $outletId = $this->resolveActorOutletId($actor);

        if ($this->shiftRepository->findActiveShiftForOutlet($outletId)) {
            throw ValidationException::withMessages([
                'shift' => 'Masih ada shift aktif di outlet ini. Tutup dulu sebelum buka shift baru.',
            ]);
        }

        $todaySchedule = $this->shiftRepository->getTodayScheduleForUser($actor->id, CarbonImmutable::today());
        $shiftTemplateId = $payload['shift_template_id'] ?? $todaySchedule?->shift_template_id;

        if ($shiftTemplateId && !$this->shiftRepository->findShiftTemplateForOutlet($shiftTemplateId, $outletId)) {
            throw ValidationException::withMessages([
                'shift_template_id' => 'Template shift tidak valid untuk outlet aktif.',
            ]);
        }

        return DB::transaction(function () use ($payload, $actor, $outletId, $shiftTemplateId) {
            $shift = $this->shiftRepository->create([
                'outlet_id' => $outletId,
                'user_id' => $actor->id,
                'shift_template_id' => $shiftTemplateId,
                'opened_by' => $actor->id,
                'closed_by' => null,
                'opened_at' => now(),
                'closed_at' => null,
                'status' => 'active',
                'opening_cash' => (float) $payload['opening_cash'],
                'expected_cash' => (float) $payload['opening_cash'],
                'actual_cash' => null,
                'cash_difference' => null,
                'notes' => filled($payload['notes'] ?? null) ? trim((string) $payload['notes']) : null,
            ]);

            $carryOverOrders = $this->shiftRepository->getCarryOverOrders($outletId);
            if ($carryOverOrders->isNotEmpty()) {
                $this->shiftRepository->assignOrdersToShift($carryOverOrders, $shift->id);
            }

            return $shift;
        });
    }

    public function close(Shift $shift, array $payload, User $actor): Shift
    {
        $this->assertCanManage($actor);
        $scopeOutletId = $this->resolveActorOutletId($actor);
        $scopedShift = $this->shiftRepository->findShiftForScope($shift->id, $scopeOutletId);

        if (!$scopedShift) {
            abort(404);
        }

        if ($scopedShift->status !== 'active') {
            throw ValidationException::withMessages([
                'shift' => 'Shift ini sudah ditutup.',
            ]);
        }

        if ($actor->role?->type === 'kasir' && $scopedShift->user_id !== $actor->id) {
            throw ValidationException::withMessages([
                'shift' => 'Kasir hanya bisa menutup shift miliknya sendiri.',
            ]);
        }

        $summary = $this->calculateSummary($scopedShift);
        $actualCash = (float) $payload['actual_cash'];
        $expectedCash = $summary['expected_cash'];
        $cashDifference = $actualCash - $expectedCash;

        return DB::transaction(function () use ($scopedShift, $payload, $actor, $summary, $actualCash, $expectedCash, $cashDifference) {
            $updatedShift = $this->shiftRepository->update($scopedShift, [
                'status' => 'closed',
                'closed_by' => $actor->id,
                'closed_at' => now(),
                'expected_cash' => $expectedCash,
                'actual_cash' => $actualCash,
                'cash_difference' => $cashDifference,
                'notes' => filled($payload['notes'] ?? null) ? trim((string) $payload['notes']) : $scopedShift->notes,
            ]);

            $this->shiftRepository->upsertCashReport($updatedShift, [
                'total_orders' => $summary['total_orders'],
                'total_revenue' => $summary['total_revenue'],
                'total_cash' => $summary['breakdown']['cash'],
                'total_qris' => $summary['breakdown']['qris'],
                'total_debit' => $summary['breakdown']['debit'],
                'total_ewallet' => $summary['breakdown']['ewallet'],
                'total_kasbon' => $summary['breakdown']['kasbon'],
                'total_discount' => $summary['total_discount'],
                'total_refund' => 0,
            ]);

            return $updatedShift->load(['user.role', 'user.outlet', 'shiftTemplate', 'opener', 'closer', 'cashReport']);
        });
     }

    public function takeover(array $payload, User $actor): void
    {
        $nextUser = User::findOrFail($payload['next_user_id']);

        // Verifikasi PIN / Password
        $verified = false;
        if ($nextUser->approval_pin && $payload['next_password_or_pin'] === $nextUser->approval_pin) {
            $verified = true;
        } elseif (Hash::check($payload['next_password_or_pin'], $nextUser->password_hash)) {
            $verified = true;
        }

        if (!$verified) {
            throw ValidationException::withMessages([
                'next_password_or_pin' => 'PIN atau Password kasir berikutnya tidak valid.',
            ]);
        }

        $activeShift = Shift::findOrFail($payload['active_shift_id']);

        if ($activeShift->status !== 'active') {
            throw ValidationException::withMessages([
                'shift' => 'Shift aktif tidak ditemukan atau sudah ditutup.',
            ]);
        }

        DB::transaction(function () use ($activeShift, $payload, $actor, $nextUser) {
            // 1. Tutup shift kasir saat ini
            $summary = $this->calculateSummary($activeShift);
            $actualCash = (float) $payload['actual_cash'];
            $expectedCash = $summary['expected_cash'];
            $cashDifference = $actualCash - $expectedCash;

            $updatedShift = $this->shiftRepository->update($activeShift, [
                'status' => 'closed',
                'closed_by' => $actor->id,
                'closed_at' => now(),
                'expected_cash' => $expectedCash,
                'actual_cash' => $actualCash,
                'cash_difference' => $cashDifference,
                'notes' => filled($payload['notes'] ?? null) ? trim((string) $payload['notes']) : $activeShift->notes,
            ]);

            $this->shiftRepository->upsertCashReport($updatedShift, [
                'total_orders' => $summary['total_orders'],
                'total_revenue' => $summary['total_revenue'],
                'total_cash' => $summary['breakdown']['cash'],
                'total_qris' => $summary['breakdown']['qris'],
                'total_debit' => $summary['breakdown']['debit'],
                'total_ewallet' => $summary['breakdown']['ewallet'],
                'total_kasbon' => $summary['breakdown']['kasbon'],
                'total_discount' => $summary['total_discount'],
                'total_refund' => 0,
            ]);

            // 2. Buka shift baru untuk kasir berikutnya
            $todaySchedule = $this->shiftRepository->getTodayScheduleForUser($nextUser->id, CarbonImmutable::today());
            $shiftTemplateId = $todaySchedule?->shift_template_id;

            if (!$shiftTemplateId) {
                $shiftTemplateId = $activeShift->shift_template_id;
            }

            $newShift = $this->shiftRepository->create([
                'outlet_id' => $activeShift->outlet_id,
                'user_id' => $nextUser->id,
                'shift_template_id' => $shiftTemplateId,
                'opened_by' => $nextUser->id,
                'closed_by' => null,
                'opened_at' => now(),
                'closed_at' => null,
                'status' => 'active',
                'opening_cash' => $actualCash,
                'expected_cash' => $actualCash,
                'actual_cash' => null,
                'cash_difference' => null,
                'notes' => 'Ambil alih shift dari ' . $actor->name,
            ]);

            // Pindahkan pesanan aktif ke shift baru
            $carryOverOrders = $this->shiftRepository->getCarryOverOrders($activeShift->outlet_id);
            if ($carryOverOrders->isNotEmpty()) {
                $this->shiftRepository->assignOrdersToShift($carryOverOrders, $newShift->id);
            }

            // 3. Login-kan user baru ke session
            Auth::login($nextUser);
        });

        // Regenerate session token setelah login user baru
        request()->session()->regenerate();
    }

    public function requireActiveShiftForOutlet(string $outletId): Shift
    {
        $shift = $this->shiftRepository->findActiveShiftForOutlet($outletId);

        if (!$shift) {
            throw ValidationException::withMessages([
                'error' => 'Belum ada shift aktif. Buka shift kasir dulu sebelum transaksi.',
            ]);
        }

        return $shift;
    }

    public function assignOrderToActiveShiftIfMissing(string $orderId, string $outletId): void
    {
        $activeShift = $this->shiftRepository->findActiveShiftForOutlet($outletId);

        if (!$activeShift) {
            throw ValidationException::withMessages([
                'error' => 'Belum ada shift aktif. Buka shift kasir dulu sebelum menyelesaikan transaksi.',
            ]);
        }

        $this->shiftRepository->setOrderShift($orderId, $activeShift->id);
    }

    protected function calculateSummary(Shift $shift): array
    {
        $orders = $this->shiftRepository->getShiftOrders($shift->id);
        $paidOrders = $orders->filter(function ($order) {
            $paymentMeta = $order->metadata['payment'] ?? [];

            return ($paymentMeta['status'] ?? null) === 'paid'
                || (float) $order->paid_amount >= (float) $order->total_amount;
        });

        $breakdown = [
            'cash' => 0.0,
            'qris' => 0.0,
            'debit' => 0.0,
            'ewallet' => 0.0,
            'kasbon' => 0.0,
        ];

        foreach ($paidOrders as $order) {
            $method = data_get($order->metadata, 'payment.method');
            $amount = (float) $order->total_amount;

            if (array_key_exists((string) $method, $breakdown)) {
                $breakdown[(string) $method] += $amount;
            }
        }

        return [
            'total_orders' => $paidOrders->count(),
            'total_revenue' => array_sum($breakdown),
            'total_discount' => (float) $paidOrders->sum(fn ($order) => (float) $order->discount_amount),
            'active_orders' => $orders->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'breakdown' => $breakdown,
            'expected_cash' => (float) $shift->opening_cash + $breakdown['cash'],
        ];
    }

    protected function transformShiftPaginator(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        $paginator->setCollection(
            $paginator->getCollection()->map(fn (Shift $shift) => $this->transformShift($shift, $shift->status === 'active')),
        );

        return $paginator;
    }

    protected function transformShift(Shift $shift, bool $isLiveSummary): array
    {
        $summary = $isLiveSummary ? $this->calculateSummary($shift) : [
            'total_orders' => (int) ($shift->cashReport?->total_orders ?? 0),
            'total_revenue' => (float) ($shift->cashReport?->total_revenue ?? 0),
            'total_discount' => (float) ($shift->cashReport?->total_discount ?? 0),
            'active_orders' => 0,
            'breakdown' => [
                'cash' => (float) ($shift->cashReport?->total_cash ?? 0),
                'qris' => (float) ($shift->cashReport?->total_qris ?? 0),
                'debit' => (float) ($shift->cashReport?->total_debit ?? 0),
                'ewallet' => (float) ($shift->cashReport?->total_ewallet ?? 0),
                'kasbon' => (float) ($shift->cashReport?->total_kasbon ?? 0),
            ],
            'expected_cash' => (float) ($shift->expected_cash ?? $shift->opening_cash ?? 0),
        ];

        return [
            'id' => $shift->id,
            'status' => $shift->status,
            'opened_at' => $shift->opened_at?->toIso8601String(),
            'closed_at' => $shift->closed_at?->toIso8601String(),
            'opening_cash' => (float) $shift->opening_cash,
            'expected_cash' => (float) ($shift->expected_cash ?? $summary['expected_cash']),
            'actual_cash' => $shift->actual_cash !== null ? (float) $shift->actual_cash : null,
            'cash_difference' => $shift->cash_difference !== null ? (float) $shift->cash_difference : null,
            'notes' => $shift->notes,
            'user' => $shift->user ? [
                'id' => $shift->user->id,
                'name' => $shift->user->name,
                'role' => $shift->user->role?->name,
            ] : null,
            'outlet' => $shift->outlet ? [
                'id' => $shift->outlet->id,
                'name' => $shift->outlet->name,
            ] : null,
            'shift_template' => $shift->shiftTemplate ? [
                'id' => $shift->shiftTemplate->id,
                'name' => $shift->shiftTemplate->name,
                'start_time' => $shift->shiftTemplate->start_time,
                'end_time' => $shift->shiftTemplate->end_time,
            ] : null,
            'summary' => $summary,
        ];
    }

    protected function resolveActorOutletId(User $actor): string
    {
        if (!$actor->outlet_id) {
            throw ValidationException::withMessages([
                'shift' => 'User belum terhubung ke outlet aktif.',
            ]);
        }

        return $actor->outlet_id;
    }

    protected function resolveScopeOutletId(User $actor, ?string $requestedOutletId): ?string
    {
        if ($actor->role?->type === 'owner') {
            return $requestedOutletId ?: ($actor->outlet_id ?: null);
        }

        if (!$actor->outlet_id) {
            throw ValidationException::withMessages([
                'shift' => 'User belum terhubung ke outlet aktif.',
            ]);
        }

        return $actor->outlet_id;
    }

    protected function assertCanManage(User $actor): void
    {
        if (!in_array($actor->role?->type, ['owner', 'kasir', 'supervisor'], true)) {
            abort(403, 'Menu shift kasir hanya tersedia untuk owner, kasir, atau supervisor.');
        }
    }

    protected function assertCanRead(User $actor): void
    {
        if (!in_array($actor->role?->type, ['owner', 'kasir', 'supervisor'], true)) {
            abort(403, 'Menu shift kasir hanya tersedia untuk owner, kasir, atau supervisor.');
        }
    }
}
