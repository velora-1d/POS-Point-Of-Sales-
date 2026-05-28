<?php

namespace App\Services;

use App\Models\ApprovalRule;
use App\Models\Order;
use App\Models\Outlet;
use App\Models\User;
use App\Repositories\ApprovalRuleRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ApprovalRuleService
{
    public function __construct(
        protected ApprovalRuleRepository $approvalRuleRepository,
        protected SecurityActivityLogService $securityActivityLogService,
    ) {
    }

    public function getDashboard(User $actor, array $filters = []): array
    {
        $this->assertCanManage($actor);

        $outlets = $this->approvalRuleRepository->getOutlets();
        $selectedOutlet = $this->resolveSelectedOutlet($outlets, $filters['outlet_id'] ?? null);
        $selectedOutletId = $selectedOutlet['id'] ?? null;
        $storedRule = $selectedOutletId
            ? $this->approvalRuleRepository->findByOutletId($selectedOutletId)
            : null;
        $defaults = $this->buildFormDefaults($selectedOutletId, $storedRule);

        return [
            'outlets' => $outlets->map(function (Outlet $outlet) {
                return [
                    'id' => $outlet->id,
                    'name' => $outlet->name,
                    'is_active' => (bool) $outlet->is_active,
                    'has_config' => (bool) $outlet->approvalRule,
                ];
            })->values()->all(),
            'selectedOutlet' => $selectedOutlet,
            'summary' => [
                'total_outlets' => $outlets->count(),
                'configured_outlets' => $outlets->filter(fn (Outlet $outlet) => $outlet->approvalRule !== null)->count(),
                'manual_discount_enabled' => $outlets->filter(fn (Outlet $outlet) => (bool) ($outlet->approvalRule?->manual_discount_enabled ?? false))->count(),
                'order_edit_enabled' => $outlets->filter(fn (Outlet $outlet) => (bool) ($outlet->approvalRule?->order_edit_enabled ?? false))->count(),
            ],
            'formDefaults' => $defaults,
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
            'manual_discount_enabled' => (bool) $payload['manual_discount_enabled'],
            'manual_discount_threshold' => round((float) $payload['manual_discount_threshold'], 2),
            'order_edit_enabled' => (bool) $payload['order_edit_enabled'],
            'order_edit_threshold' => round((float) $payload['order_edit_threshold'], 2),
            'metadata' => [
                'updated_by' => $actor->id,
                'updated_at' => now()->toIso8601String(),
            ],
        ];

        $this->approvalRuleRepository->upsertByOutlet($outlet->id, $normalized);
        $this->securityActivityLogService->log(
            $actor,
            $outlet->id,
            'approval_rules.updated',
            'Approval rules untuk outlet diperbarui.',
            'success',
            [
                'manual_discount_enabled' => $normalized['manual_discount_enabled'],
                'manual_discount_threshold' => $normalized['manual_discount_threshold'],
                'order_edit_enabled' => $normalized['order_edit_enabled'],
                'order_edit_threshold' => $normalized['order_edit_threshold'],
            ],
            $ipAddress,
        );

        return $outlet->id;
    }

    public function assertManualDiscountApproval(string $outletId, array $pricing, ?string $approvalPin): bool
    {
        $rule = $this->resolveRuntimeRule($outletId);
        $manualDiscountAmount = $this->resolveManualDiscountAmount($pricing);

        if (!$rule->manual_discount_enabled || $manualDiscountAmount <= 0) {
            return false;
        }

        if ($manualDiscountAmount < (float) $rule->manual_discount_threshold) {
            return false;
        }

        $this->validateOwnerApproval($outletId, $approvalPin, 'Diskon manual di atas threshold membutuhkan PIN owner.');

        return true;
    }

    public function assertOrderEditApproval(Order $order, float $editedTotalAmount, ?string $approvalPin): bool
    {
        $rule = $this->resolveRuntimeRule($order->outlet_id);

        if (!$rule->order_edit_enabled) {
            return false;
        }

        if ($editedTotalAmount < (float) $rule->order_edit_threshold) {
            return false;
        }

        $this->validateOwnerApproval($order->outlet_id, $approvalPin, 'Edit order di atas threshold membutuhkan PIN owner.');

        return true;
    }

    protected function resolveManualDiscountAmount(array $pricing): float
    {
        return round((float) collect($pricing['applied_promos'] ?? [])
            ->filter(fn (array $promo) => ($promo['source'] ?? null) === 'manual')
            ->sum('discount_amount'), 2);
    }

    protected function validateOwnerApproval(string $outletId, ?string $approvalPin, string $message): void
    {
        if (!$approvalPin) {
            throw ValidationException::withMessages([
                'approval_pin' => $message,
            ]);
        }

        $owners = User::query()
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->whereHas('role', function ($query) {
                $query->where('type', 'owner');
            })
            ->get();

        $isValid = $owners->contains(function (User $user) use ($approvalPin) {
            return $user->approval_pin && Hash::check($approvalPin, $user->approval_pin);
        });

        if (!$isValid) {
            throw ValidationException::withMessages([
                'approval_pin' => 'PIN owner tidak valid.',
            ]);
        }
    }

    protected function resolveRuntimeRule(string $outletId): ApprovalRule
    {
        return $this->approvalRuleRepository->findByOutletId($outletId)
            ?? new ApprovalRule([
                'outlet_id' => $outletId,
                'manual_discount_enabled' => true,
                'manual_discount_threshold' => 50000,
                'order_edit_enabled' => true,
                'order_edit_threshold' => 150000,
            ]);
    }

    protected function buildFormDefaults(?string $outletId, ?ApprovalRule $storedRule): array
    {
        $rule = $storedRule ?? $this->resolveRuntimeRule((string) $outletId);

        return [
            'outlet_id' => $outletId,
            'manual_discount_enabled' => (bool) $rule->manual_discount_enabled,
            'manual_discount_threshold' => (float) $rule->manual_discount_threshold,
            'order_edit_enabled' => (bool) $rule->order_edit_enabled,
            'order_edit_threshold' => (float) $rule->order_edit_threshold,
            'has_config' => $storedRule !== null,
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
        $outlet = $this->approvalRuleRepository->getOutlets()->firstWhere('id', $outletId);

        if (!$outlet instanceof Outlet) {
            abort(404);
        }

        return $outlet;
    }

    protected function assertCanManage(User $actor): void
    {
        if ($actor->role?->type !== 'owner') {
            abort(403, 'Approval rules hanya tersedia untuk owner.');
        }
    }
}
