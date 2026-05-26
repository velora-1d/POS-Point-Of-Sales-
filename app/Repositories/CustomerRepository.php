<?php

namespace App\Repositories;

use App\Models\Customer;
use App\Models\MembershipTier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CustomerRepository
{
    public function paginateByOutlet(string $outletId, array $filters = []): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $perPage = (int) ($filters['per_page'] ?? 12);

        return Customer::query()
            ->where('outlet_id', $outletId)
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'ilike', '%' . $search . '%')
                        ->orWhere('phone', 'ilike', '%' . $search . '%')
                        ->orWhere('email', 'ilike', '%' . $search . '%');
                });
            })
            ->with([
                'membership.tier',
                'latestOrder.table',
                'recentOrders.table',
                'latestKasbonOrder.table',
            ])
            ->withCount('orders')
            ->withCount([
                'kasbonOrders as kasbon_orders_count',
            ])
            ->withSum([
                'orders as total_spent' => function (Builder $query) {
                    $query->whereNotIn('status', ['cancelled']);
                },
            ], 'total_amount')
            ->addSelect([
                'kasbon_total_due' => DB::table('orders')
                    ->selectRaw('COALESCE(SUM(total_amount - paid_amount), 0)')
                    ->whereColumn('orders.customer_id', 'customers.id')
                    ->whereColumn('orders.paid_amount', '<', 'orders.total_amount')
                    ->where('orders.status', '!=', 'cancelled'),
            ])
            ->latest()
            ->paginate(max(6, min($perPage, 24)))
            ->withQueryString();
    }

    public function getSummary(string $outletId): array
    {
        $baseQuery = Customer::query()->where('outlet_id', $outletId);

        return [
            'total' => (clone $baseQuery)->count(),
            'members' => (clone $baseQuery)
                ->whereHas('membership', fn (Builder $query) => $query->where('is_active', true))
                ->count(),
            'withOrders' => (clone $baseQuery)
                ->whereHas('orders')
                ->count(),
            'registeredThisMonth' => (clone $baseQuery)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];
    }

    public function getLoyaltySummary(string $outletId): array
    {
        $membershipQuery = DB::table('memberships')
            ->join('customers', 'customers.id', '=', 'memberships.customer_id')
            ->where('customers.outlet_id', $outletId)
            ->where('memberships.is_active', true);

        $topTier = MembershipTier::query()
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->orderByDesc('point_threshold')
            ->first();

        return [
            'activeMembers' => (clone $membershipQuery)->count(),
            'totalPoints' => (int) ((clone $membershipQuery)->sum('memberships.total_points') ?? 0),
            'lifetimePoints' => (int) ((clone $membershipQuery)->sum('memberships.lifetime_points') ?? 0),
            'activeTiers' => MembershipTier::query()
                ->where('outlet_id', $outletId)
                ->where('is_active', true)
                ->count(),
            'topTierName' => $topTier?->name,
        ];
    }

    public function getTierBreakdown(string $outletId)
    {
        return MembershipTier::query()
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->withCount([
                'memberships as active_members_count' => function (Builder $query) {
                    $query->where('is_active', true);
                },
            ])
            ->orderBy('point_threshold')
            ->get();
    }

    public function getTopLoyalCustomers(string $outletId)
    {
        return Customer::query()
            ->where('outlet_id', $outletId)
            ->whereHas('membership', fn (Builder $query) => $query->where('is_active', true))
            ->with([
                'membership.tier',
                'latestOrder.table',
            ])
            ->withCount('orders')
            ->orderByDesc(
                DB::table('memberships')
                    ->select('lifetime_points')
                    ->whereColumn('memberships.customer_id', 'customers.id')
                    ->limit(1)
            )
            ->limit(5)
            ->get();
    }

    public function getKasbonSummary(string $outletId): array
    {
        $outstandingQuery = DB::table('orders')
            ->join('customers', 'customers.id', '=', 'orders.customer_id')
            ->where('customers.outlet_id', $outletId)
            ->whereColumn('orders.paid_amount', '<', 'orders.total_amount')
            ->where('orders.status', '!=', 'cancelled');

        return [
            'customersWithDebt' => (clone $outstandingQuery)
                ->distinct('orders.customer_id')
                ->count('orders.customer_id'),
            'openKasbonOrders' => (clone $outstandingQuery)->count(),
            'totalOutstanding' => (float) ((clone $outstandingQuery)->sum(DB::raw('orders.total_amount - orders.paid_amount')) ?? 0),
        ];
    }

    public function getTopKasbonCustomers(string $outletId)
    {
        return Customer::query()
            ->where('outlet_id', $outletId)
            ->whereHas('kasbonOrders')
            ->with([
                'latestKasbonOrder.table',
            ])
            ->withCount([
                'kasbonOrders as kasbon_orders_count',
            ])
            ->addSelect([
                'kasbon_total_due' => DB::table('orders')
                    ->selectRaw('COALESCE(SUM(total_amount - paid_amount), 0)')
                    ->whereColumn('orders.customer_id', 'customers.id')
                    ->whereColumn('orders.paid_amount', '<', 'orders.total_amount')
                    ->where('orders.status', '!=', 'cancelled'),
            ])
            ->orderByDesc('kasbon_total_due')
            ->limit(5)
            ->get();
    }
}
