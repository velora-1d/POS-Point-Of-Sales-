<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\MembershipTier;
use App\Models\Product;
use App\Models\Promo;
use App\Models\PromoRule;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class PromoRepository
{
    public function paginateByOutlet(string $outletId, array $filters = []): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = $filters['status'] ?? null;
        $type = $filters['type'] ?? null;
        $applyMethod = $filters['apply_method'] ?? null;
        $perPage = (int) ($filters['per_page'] ?? 12);

        return Promo::query()
            ->where('outlet_id', $outletId)
            ->with('rules')
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'ilike', '%' . $search . '%')
                        ->orWhere('code', 'ilike', '%' . $search . '%');
                });
            })
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($type, fn (Builder $query) => $query->where('type', $type))
            ->when($applyMethod, fn (Builder $query) => $query->where('apply_method', $applyMethod))
            ->orderByRaw("
                CASE status
                    WHEN 'active' THEN 1
                    WHEN 'inactive' THEN 2
                    WHEN 'expired' THEN 3
                    ELSE 4
                END
            ")
            ->latest('start_date')
            ->paginate(max(6, min($perPage, 24)))
            ->withQueryString();
    }

    public function getSummary(string $outletId): array
    {
        $baseQuery = Promo::query()->where('outlet_id', $outletId);

        return [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('status', 'active')->count(),
            'auto_apply' => (clone $baseQuery)->whereIn('apply_method', ['auto', 'both'])->count(),
            'stackable' => (clone $baseQuery)->where('can_stack', true)->count(),
        ];
    }

    public function getProducts(string $outletId): Collection
    {
        return Product::query()
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'category_id']);
    }

    public function getCategories(string $outletId): Collection
    {
        return Category::query()
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getMembershipTiers(string $outletId): Collection
    {
        return MembershipTier::query()
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->orderBy('point_threshold')
            ->get(['id', 'name', 'tier', 'discount_percent']);
    }

    public function create(array $payload): Promo
    {
        return Promo::query()->create($payload);
    }

    public function getPromoEngineCatalog(string $outletId): EloquentCollection
    {
        return Promo::query()
            ->where('outlet_id', $outletId)
            ->with('rules')
            ->get();
    }

    public function update(Promo $promo, array $payload): void
    {
        $promo->update($payload);
    }

    public function replaceRules(Promo $promo, array $rules): void
    {
        PromoRule::query()
            ->where('promo_id', $promo->id)
            ->delete();

        foreach ($rules as $rule) {
            PromoRule::query()->create([
                'promo_id' => $promo->id,
                'trigger' => $rule['trigger'],
                'reference_id' => $rule['reference_id'],
                'reference_value' => $rule['reference_value'],
            ]);
        }
    }

    public function incrementUsageCounts(array $promoIds, int $amount = 1): void
    {
        if (!$promoIds || $amount <= 0) {
            return;
        }

        Promo::query()
            ->whereIn('id', array_unique($promoIds))
            ->increment('usage_count', $amount);
    }

    public function decrementUsageCounts(array $promoIds, int $amount = 1): void
    {
        if (!$promoIds || $amount <= 0) {
            return;
        }

        Promo::query()
            ->whereIn('id', array_unique($promoIds))
            ->where('usage_count', '>', 0)
            ->update([
                'usage_count' => DB::raw('GREATEST(usage_count - ' . $amount . ', 0)'),
            ]);
    }
}
