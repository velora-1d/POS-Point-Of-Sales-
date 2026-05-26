<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\EmployeeRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EmployeeService
{
    public function __construct(
        protected EmployeeRepository $employeeRepository,
    ) {
    }

    public function getDashboard(User $actor, array $filters = []): array
    {
        $canManage = $this->canManage($actor);
        $canRead = $canManage || $actor->role?->type === 'supervisor';

        if (!$canRead) {
            abort(403, 'Menu karyawan hanya tersedia untuk owner atau supervisor.');
        }

        $scopeOutletId = $canManage ? null : $actor->outlet_id;
        $resolvedFilters = $filters;

        if ($scopeOutletId) {
            $resolvedFilters['outlet_id'] = $scopeOutletId;
        }

        return [
            'employees' => $this->employeeRepository->paginate($resolvedFilters, $scopeOutletId),
            'summary' => $this->employeeRepository->getSummary($scopeOutletId),
            'roleBreakdown' => $this->employeeRepository->getRoleBreakdown($scopeOutletId),
            'filters' => [
                'search' => (string) ($resolvedFilters['search'] ?? ''),
                'status' => $resolvedFilters['status'] ?: '',
                'role_type' => $resolvedFilters['role_type'] ?: '',
                'outlet_id' => $resolvedFilters['outlet_id'] ?: '',
                'per_page' => (int) ($resolvedFilters['per_page'] ?? 12),
            ],
            'referenceData' => [
                'outlets' => $this->employeeRepository->getOutlets($scopeOutletId),
                'roles' => $this->employeeRepository->getRoles($scopeOutletId),
            ],
            'canManage' => $canManage,
        ];
    }

    public function create(array $payload, User $actor): void
    {
        $this->assertCanManage($actor);

        DB::transaction(function () use ($payload) {
            $normalized = $this->normalizePayload($payload, true);
            $this->employeeRepository->create($normalized);
        });
    }

    public function update(User $employee, array $payload, User $actor): void
    {
        $this->assertCanManage($actor);

        DB::transaction(function () use ($employee, $payload) {
            $normalized = $this->normalizePayload($payload, false);
            $this->employeeRepository->update($employee, $normalized);
        });
    }

    protected function normalizePayload(array $payload, bool $isCreate): array
    {
        $outletId = $payload['outlet_id'];
        $role = $this->employeeRepository->findRoleForOutlet($payload['role_id'], $outletId);

        if (!$role) {
            throw ValidationException::withMessages([
                'role_id' => 'Role tidak valid untuk outlet yang dipilih.',
            ]);
        }

        $normalized = [
            'outlet_id' => $outletId,
            'role_id' => $role->id,
            'name' => trim((string) $payload['name']),
            'email' => strtolower(trim((string) $payload['email'])),
            'phone' => trim((string) $payload['phone']),
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'join_date' => $payload['join_date'],
        ];

        if ($isCreate || filled($payload['password'] ?? null)) {
            $normalized['password_hash'] = Hash::make((string) $payload['password']);
        }

        if ($isCreate || filled($payload['approval_pin'] ?? null)) {
            $normalized['approval_pin'] = Hash::make((string) $payload['approval_pin']);
        }

        return $normalized;
    }

    protected function canManage(User $actor): bool
    {
        return $actor->role?->type === 'owner';
    }

    protected function assertCanManage(User $actor): void
    {
        if (!$this->canManage($actor)) {
            abort(403, 'Hanya owner yang bisa mengelola data karyawan.');
        }
    }
}
