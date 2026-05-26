<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Promo;
use App\Repositories\PromoRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PromoEngineService
{
    public function __construct(
        protected PromoRepository $promoRepository,
    ) {
    }

    public function calculate(
        string $outletId,
        array $items,
        ?Customer $customer = null,
        ?string $paymentMethod = null,
        ?string $promoCode = null,
    ): array {
        $normalizedItems = collect($items)->map(function (array $item) {
            return [
                'product_id' => $item['product_id'],
                'category_id' => $item['category_id'] ?? null,
                'quantity' => (int) ($item['quantity'] ?? 0),
                'unit_price' => (float) ($item['unit_price'] ?? 0),
                'total_price' => (float) ($item['total_price'] ?? 0),
            ];
        })->filter(fn (array $item) => $item['quantity'] > 0)->values();

        $subtotal = (float) $normalizedItems->sum('total_price');
        $manualCode = $this->normalizePromoCode($promoCode);

        if ($subtotal <= 0 || $normalizedItems->isEmpty()) {
            return [
                'subtotal' => 0,
                'discount_amount' => 0,
                'total_amount' => 0,
                'manual_code' => $manualCode,
                'applied_promos' => [],
            ];
        }

        $catalog = $this->promoRepository->getPromoEngineCatalog($outletId);
        $tierId = $customer?->membership?->tier?->id;
        $now = CarbonImmutable::now();

        $eligibleAutoPromos = $catalog
            ->filter(fn (Promo $promo) => in_array($promo->apply_method, ['auto', 'both'], true))
            ->map(fn (Promo $promo) => $this->evaluatePromo($promo, $normalizedItems, $subtotal, $tierId, $paymentMethod, $now))
            ->filter(fn (array $evaluation) => $evaluation['eligible'])
            ->values();

        $eligiblePromos = $eligibleAutoPromos;

        if ($manualCode) {
            $manualPromo = $catalog->first(function (Promo $promo) use ($manualCode) {
                return strtoupper((string) $promo->code) === $manualCode
                    && in_array($promo->apply_method, ['manual', 'both'], true);
            });

            if (!$manualPromo) {
                throw ValidationException::withMessages([
                    'promo_code' => 'Kode voucher tidak ditemukan atau tidak aktif untuk input manual.',
                ]);
            }

            $manualEvaluation = $this->evaluatePromo(
                $manualPromo,
                $normalizedItems,
                $subtotal,
                $tierId,
                $paymentMethod,
                $now,
            );

            if (!$manualEvaluation['eligible']) {
                throw ValidationException::withMessages([
                    'promo_code' => $manualEvaluation['reason'] ?: 'Kode voucher tidak memenuhi syarat transaksi ini.',
                ]);
            }

            $manualEvaluation['source'] = 'manual';

            $eligiblePromos = $eligiblePromos
                ->reject(fn (array $promo) => $promo['id'] === $manualEvaluation['id'])
                ->push($manualEvaluation)
                ->values();
        }

        $selectedPromos = $this->selectPromosToApply($eligiblePromos);
        $discountAmount = round((float) $selectedPromos->sum('discount_amount'), 2);

        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => $discountAmount,
            'total_amount' => round(max(0, $subtotal - $discountAmount), 2),
            'manual_code' => $manualCode,
            'applied_promos' => $selectedPromos
                ->map(function (array $promo) {
                    return [
                        'id' => $promo['id'],
                        'name' => $promo['name'],
                        'code' => $promo['code'],
                        'type' => $promo['type'],
                        'apply_method' => $promo['apply_method'],
                        'source' => $promo['source'],
                        'discount_amount' => round((float) $promo['discount_amount'], 2),
                        'can_stack' => $promo['can_stack'],
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    public function extractPromoIdsFromMetadata(?array $metadata): array
    {
        return collect($metadata['promo']['applied_promos'] ?? [])
            ->pluck('id')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function syncUsageDifference(array $previousPromoIds, array $currentPromoIds): void
    {
        $previousCounts = collect($previousPromoIds)
            ->filter()
            ->countBy();
        $currentCounts = collect($currentPromoIds)
            ->filter()
            ->countBy();

        $allPromoIds = $previousCounts
            ->keys()
            ->merge($currentCounts->keys())
            ->unique()
            ->values();

        foreach ($allPromoIds as $promoId) {
            $previousCount = (int) $previousCounts->get($promoId, 0);
            $currentCount = (int) $currentCounts->get($promoId, 0);

            if ($currentCount > $previousCount) {
                $this->promoRepository->incrementUsageCounts([$promoId], $currentCount - $previousCount);
            }

            if ($previousCount > $currentCount) {
                $this->promoRepository->decrementUsageCounts([$promoId], $previousCount - $currentCount);
            }
        }
    }

    protected function evaluatePromo(
        Promo $promo,
        Collection $items,
        float $subtotal,
        ?string $tierId,
        ?string $paymentMethod,
        CarbonImmutable $now,
    ): array {
        $reason = $this->validatePromoEligibility($promo, $items, $subtotal, $tierId, $paymentMethod, $now);

        if ($reason !== null) {
            return [
                'id' => $promo->id,
                'eligible' => false,
                'reason' => $reason,
            ];
        }

        $matchedItems = $this->resolveMatchedItems($promo, $items);
        $discountAmount = $this->calculateDiscountAmount($promo, $matchedItems, $subtotal);

        if ($discountAmount <= 0) {
            return [
                'id' => $promo->id,
                'eligible' => false,
                'reason' => 'Promo tidak menghasilkan potongan pada kombinasi item saat ini.',
            ];
        }

        return [
            'id' => $promo->id,
            'name' => $promo->name,
            'code' => $promo->code,
            'type' => $promo->type,
            'apply_method' => $promo->apply_method,
            'source' => in_array($promo->apply_method, ['auto', 'both'], true) ? 'auto' : 'manual',
            'can_stack' => (bool) $promo->can_stack,
            'discount_amount' => round($discountAmount, 2),
            'eligible' => true,
            'reason' => null,
        ];
    }

    protected function validatePromoEligibility(
        Promo $promo,
        Collection $items,
        float $subtotal,
        ?string $tierId,
        ?string $paymentMethod,
        CarbonImmutable $now,
    ): ?string {
        if ($promo->status !== 'active') {
            return 'Promo sedang tidak aktif.';
        }

        if ($promo->start_date && $now->lt(CarbonImmutable::parse($promo->start_date))) {
            return 'Promo belum masuk periode aktif.';
        }

        if ($promo->end_date && $now->gt(CarbonImmutable::parse($promo->end_date))) {
            return 'Promo sudah melewati masa berlaku.';
        }

        if ($promo->usage_limit !== null && (int) $promo->usage_count >= (int) $promo->usage_limit) {
            return 'Kuota voucher atau promo sudah habis.';
        }

        if ($promo->min_transaction_amount !== null && $subtotal < (float) $promo->min_transaction_amount) {
            return 'Total transaksi belum memenuhi minimal belanja promo.';
        }

        $rules = $promo->rules ?? collect();
        $productRuleIds = $rules->where('trigger', 'product')->pluck('reference_id')->filter();
        $categoryRuleIds = $rules->where('trigger', 'category')->pluck('reference_id')->filter();
        $paymentMethods = $rules->where('trigger', 'payment_method')->pluck('reference_value')->filter()->map(fn ($value) => strtolower((string) $value));
        $memberTierIds = $rules->where('trigger', 'member_tier')->pluck('reference_id')->filter();
        $activeDays = $rules->where('trigger', 'time')->pluck('reference_value')->filter()->map(fn ($value) => strtolower((string) $value));

        if ($productRuleIds->isNotEmpty() && !$items->contains(fn (array $item) => $productRuleIds->contains($item['product_id']))) {
            return 'Produk di keranjang belum memenuhi syarat promo.';
        }

        if ($categoryRuleIds->isNotEmpty() && !$items->contains(fn (array $item) => $categoryRuleIds->contains($item['category_id']))) {
            return 'Kategori item belum sesuai dengan trigger promo.';
        }

        if ($memberTierIds->isNotEmpty() && (!$tierId || !$memberTierIds->contains($tierId))) {
            return 'Tier member pelanggan belum memenuhi syarat promo.';
        }

        if ($paymentMethods->isNotEmpty()) {
            if (!$paymentMethod) {
                return 'Promo ini baru berlaku saat metode bayar yang sesuai dipilih.';
            }

            if (!$paymentMethods->contains(strtolower($paymentMethod))) {
                return 'Promo ini tidak berlaku untuk metode bayar yang dipilih.';
            }
        }

        if ($activeDays->isNotEmpty()) {
            $currentDay = strtolower($now->englishDayOfWeek);

            if (!$activeDays->contains($currentDay)) {
                return 'Promo ini tidak aktif di hari transaksi saat ini.';
            }
        }

        if (($promo->happy_hour_start || $promo->happy_hour_end) && !$this->isWithinHappyHour($promo, $now)) {
            return 'Promo ini hanya aktif pada jam happy hour yang ditentukan.';
        }

        if ($promo->type === 'buy_x_get_y') {
            $matchedItems = $this->resolveMatchedItems($promo, $items);
            $matchedQuantity = (int) $matchedItems->sum('quantity');
            $bundleSize = (int) $promo->buy_quantity + (int) $promo->get_quantity;

            if ($matchedQuantity < $bundleSize || $bundleSize <= 0) {
                return 'Jumlah item belum cukup untuk promo buy X get Y.';
            }
        }

        return null;
    }

    protected function resolveMatchedItems(Promo $promo, Collection $items): Collection
    {
        $rules = $promo->rules ?? collect();
        $productRuleIds = $rules->where('trigger', 'product')->pluck('reference_id')->filter()->values();
        $categoryRuleIds = $rules->where('trigger', 'category')->pluck('reference_id')->filter()->values();

        return $items->filter(function (array $item) use ($productRuleIds, $categoryRuleIds) {
            if ($productRuleIds->isNotEmpty() && !$productRuleIds->contains($item['product_id'])) {
                return false;
            }

            if ($categoryRuleIds->isNotEmpty() && !$categoryRuleIds->contains($item['category_id'])) {
                return false;
            }

            return true;
        })->values();
    }

    protected function calculateDiscountAmount(Promo $promo, Collection $matchedItems, float $subtotal): float
    {
        $baseAmount = $matchedItems->isEmpty()
            ? $subtotal
            : (float) $matchedItems->sum('total_price');

        if ($promo->type === 'percent') {
            $discountAmount = $baseAmount * ((float) $promo->discount_percent / 100);

            if ($promo->max_discount_amount !== null) {
                $discountAmount = min($discountAmount, (float) $promo->max_discount_amount);
            }

            return $discountAmount;
        }

        if ($promo->type === 'nominal') {
            return min((float) $promo->discount_amount, $baseAmount);
        }

        if ($promo->type === 'buy_x_get_y') {
            $expandedPrices = $matchedItems
                ->flatMap(function (array $item) {
                    return collect(range(1, max(0, $item['quantity'])))->map(
                        fn () => (float) $item['unit_price']
                    );
                })
                ->sort()
                ->values();

            $bundleSize = (int) $promo->buy_quantity + (int) $promo->get_quantity;

            if ($bundleSize <= 0) {
                return 0;
            }

            $freeUnits = intdiv($expandedPrices->count(), $bundleSize) * (int) $promo->get_quantity;

            return (float) $expandedPrices->take($freeUnits)->sum();
        }

        return 0;
    }

    protected function selectPromosToApply(Collection $eligiblePromos): Collection
    {
        if ($eligiblePromos->isEmpty()) {
            return collect();
        }

        if ($eligiblePromos->contains(fn (array $promo) => !$promo['can_stack'])) {
            $bestPromo = $eligiblePromos
                ->sortByDesc('discount_amount')
                ->first();

            return collect($bestPromo ? [$bestPromo] : []);
        }

        return $eligiblePromos->values();
    }

    protected function isWithinHappyHour(Promo $promo, CarbonImmutable $now): bool
    {
        if (!$promo->happy_hour_start || !$promo->happy_hour_end) {
            return true;
        }

        $start = $this->parseTime($promo->happy_hour_start, $now);
        $end = $this->parseTime($promo->happy_hour_end, $now);

        if ($end->lt($start)) {
            return $now->greaterThanOrEqualTo($start) || $now->lessThanOrEqualTo($end);
        }

        return $now->greaterThanOrEqualTo($start) && $now->lessThanOrEqualTo($end);
    }

    protected function parseTime(string $value, CarbonImmutable $now): CarbonImmutable
    {
        $time = substr($value, 0, 5);

        return CarbonImmutable::parse($now->format('Y-m-d') . ' ' . $time);
    }

    protected function normalizePromoCode(?string $promoCode): ?string
    {
        $normalized = strtoupper(trim((string) $promoCode));

        return $normalized !== '' ? $normalized : null;
    }
}
