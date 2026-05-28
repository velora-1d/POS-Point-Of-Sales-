<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\Outlet;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class OutletManagementRepository
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = $filters['status'] ?? null;
        $perPage = (int) ($filters['per_page'] ?? 9);

        return Outlet::query()
            ->withCount([
                'users',
                'users as active_users_count' => fn (Builder $query) => $query->where('is_active', true),
                'tables',
                'tables as active_tables_count' => fn (Builder $query) => $query->where('is_active', true),
            ])
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'ilike', '%' . $search . '%')
                        ->orWhere('address', 'ilike', '%' . $search . '%')
                        ->orWhere('phone', 'ilike', '%' . $search . '%');
                });
            })
            ->when($status === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(max(6, min($perPage, 18)))
            ->withQueryString();
    }

    public function getSummary(): array
    {
        $baseQuery = Outlet::query();

        return [
            'total_outlets' => (clone $baseQuery)->count(),
            'active_outlets' => (clone $baseQuery)->where('is_active', true)->count(),
            'inactive_outlets' => (clone $baseQuery)->where('is_active', false)->count(),
            'active_employees' => User::query()->where('is_active', true)->count(),
        ];
    }

    public function getActiveOutlets(): Collection
    {
        return Outlet::query()
            ->where('is_active', true)
            ->withCount([
                'users as active_users_count' => fn (Builder $query) => $query->where('is_active', true),
            ])
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getPeriodMetricsForOutlets(
        array $outletIds,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ): Collection {
        if ($outletIds === []) {
            return collect();
        }

        return Order::query()
            ->selectRaw('outlet_id, COUNT(*) as total_orders, SUM(total_amount) as total_revenue')
            ->whereIn('outlet_id', $outletIds)
            ->where('status', '!=', 'cancelled')
            ->whereColumn('paid_amount', '>=', 'total_amount')
            ->whereDate('created_at', '>=', $startDate->toDateString())
            ->whereDate('created_at', '<=', $endDate->toDateString())
            ->groupBy('outlet_id')
            ->get()
            ->keyBy('outlet_id');
    }

    public function getRoleTemplates(?string $preferredOutletId = null): Collection
    {
        if ($preferredOutletId) {
            $preferredTemplates = Role::query()
                ->where('outlet_id', $preferredOutletId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['name', 'type', 'is_active']);

            if ($preferredTemplates->isNotEmpty()) {
                return $preferredTemplates;
            }
        }

        $sourceOutletId = Role::query()
            ->whereNotNull('outlet_id')
            ->where('is_active', true)
            ->orderBy('outlet_id')
            ->value('outlet_id');

        if (!$sourceOutletId) {
            return collect();
        }

        return Role::query()
            ->where('outlet_id', $sourceOutletId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['name', 'type', 'is_active']);
    }

    public function create(array $payload): Outlet
    {
        return Outlet::query()->create([
            'id' => (string) Str::uuid(),
            ...$payload,
        ]);
    }

    public function update(Outlet $outlet, array $payload): void
    {
        $outlet->update($payload);
    }

    public function createRoleTemplatesForOutlet(string $outletId, Collection $templates): void
    {
        $now = now();
        $rows = $templates
            ->map(function ($template) use ($outletId, $now) {
                return [
                    'id' => (string) Str::uuid(),
                    'outlet_id' => $outletId,
                    'name' => is_array($template) ? $template['name'] : $template->name,
                    'type' => is_array($template) ? $template['type'] : $template->type,
                    'is_active' => is_array($template) ? (bool) ($template['is_active'] ?? true) : (bool) $template->is_active,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->values()
            ->all();

        if ($rows === []) {
            return;
        }

        Role::query()->insert($rows);
    }

    public function countActiveOutlets(): int
    {
        return Outlet::query()->where('is_active', true)->count();
    }
}
