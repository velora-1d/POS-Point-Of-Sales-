<?php

namespace App\Services;

use App\Models\Outlet;
use App\Models\Promo;
use App\Models\User;
use App\Repositories\PromoRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PromoService
{
    protected const PAYMENT_METHODS = ['cash', 'qris', 'card', 'transfer'];

    protected const DAY_OPTIONS = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    public function __construct(
        protected PromoRepository $promoRepository,
    ) {
    }

    public function getDashboard(?User $user, array $filters = []): array
    {
        $this->assertOwner($user);

        $outletId = $user?->outlet_id ?? Outlet::query()->value('id');

        if (!$outletId) {
            throw ValidationException::withMessages([
                'error' => 'Belum ada outlet terdaftar di sistem.',
            ]);
        }

        return [
            'promos' => $this->promoRepository->paginateByOutlet($outletId, $filters),
            'summary' => $this->promoRepository->getSummary($outletId),
            'filters' => [
                'search' => (string) ($filters['search'] ?? ''),
                'status' => $filters['status'] ?? '',
                'type' => $filters['type'] ?? '',
                'apply_method' => $filters['apply_method'] ?? '',
                'per_page' => (int) ($filters['per_page'] ?? 12),
            ],
            'referenceData' => [
                'products' => $this->promoRepository->getProducts($outletId),
                'categories' => $this->promoRepository->getCategories($outletId),
                'membershipTiers' => $this->promoRepository->getMembershipTiers($outletId),
                'paymentMethods' => self::PAYMENT_METHODS,
                'dayOptions' => self::DAY_OPTIONS,
            ],
        ];
    }

    public function create(array $payload, User $actor): void
    {
        $this->assertOwner($actor);

        DB::transaction(function () use ($payload, $actor) {
            $normalized = $this->normalizePayload($payload, $actor->outlet_id, $actor, true);

            $promo = $this->promoRepository->create($normalized['promo']);
            $this->promoRepository->replaceRules($promo, $normalized['rules']);
        });
    }

    public function update(Promo $promo, array $payload, User $actor): void
    {
        $this->assertOwner($actor);
        $this->assertSameOutlet($promo, $actor);

        DB::transaction(function () use ($promo, $payload, $actor) {
            $normalized = $this->normalizePayload($payload, $promo->outlet_id, $actor, false);

            $this->promoRepository->update($promo, $normalized['promo']);
            $this->promoRepository->replaceRules($promo, $normalized['rules']);
        });
    }

    protected function normalizePayload(
        array $payload,
        string $outletId,
        User $actor,
        bool $includeCreator,
    ): array
    {
        $applyMethod = (string) $payload['apply_method'];
        $type = (string) $payload['type'];
        $rules = collect($payload['rules'] ?? [])
            ->map(function (array $rule) {
                return [
                    'trigger' => (string) ($rule['trigger'] ?? ''),
                    'reference_id' => filled($rule['reference_id'] ?? null) ? (string) $rule['reference_id'] : null,
                    'reference_value' => filled($rule['reference_value'] ?? null)
                        ? strtolower(trim((string) $rule['reference_value']))
                        : null,
                ];
            })
            ->filter(fn (array $rule) => filled($rule['trigger']))
            ->values();

        $this->validateBusinessRules($payload, $rules->all(), $outletId);

        $promoPayload = [
                'outlet_id' => $outletId,
                'name' => trim((string) $payload['name']),
                'code' => $applyMethod === 'auto' ? null : strtoupper(trim((string) ($payload['code'] ?? ''))),
                'type' => $type,
                'apply_method' => $applyMethod,
                'discount_percent' => $type === 'percent' ? ($payload['discount_percent'] ?? null) : null,
                'discount_amount' => $type === 'nominal' ? ($payload['discount_amount'] ?? null) : null,
                'max_discount_amount' => $type === 'percent' ? (($payload['max_discount_amount'] ?? null) ?: null) : null,
                'min_transaction_amount' => ($payload['min_transaction_amount'] ?? 0) ?: 0,
                'buy_quantity' => $type === 'buy_x_get_y' ? ($payload['buy_quantity'] ?? null) : null,
                'get_quantity' => $type === 'buy_x_get_y' ? ($payload['get_quantity'] ?? null) : null,
                'can_stack' => (bool) ($payload['can_stack'] ?? false),
                'usage_limit' => ($payload['usage_limit'] ?? null) ?: null,
                'start_date' => $payload['start_date'],
                'end_date' => ($payload['end_date'] ?? null) ?: null,
                'happy_hour_start' => ($payload['happy_hour_start'] ?? null) ?: null,
                'happy_hour_end' => ($payload['happy_hour_end'] ?? null) ?: null,
                'status' => $payload['status'],
            ];

        if ($includeCreator) {
            $promoPayload['created_by'] = $actor->id;
        }

        return [
            'promo' => $promoPayload,
            'rules' => $rules->all(),
        ];
    }

    protected function validateBusinessRules(array $payload, array $rules, string $outletId): void
    {
        $applyMethod = (string) $payload['apply_method'];
        $type = (string) $payload['type'];

        if ($applyMethod !== 'auto' && blank($payload['code'] ?? null)) {
            throw ValidationException::withMessages([
                'code' => 'Kode promo wajib diisi untuk promo manual atau kombinasi manual+auto.',
            ]);
        }

        if ($type === 'percent' && (float) ($payload['discount_percent'] ?? 0) <= 0) {
            throw ValidationException::withMessages([
                'discount_percent' => 'Diskon persen wajib lebih dari 0.',
            ]);
        }

        if ($type === 'nominal' && (float) ($payload['discount_amount'] ?? 0) <= 0) {
            throw ValidationException::withMessages([
                'discount_amount' => 'Diskon nominal wajib lebih dari 0.',
            ]);
        }

        if ($type === 'buy_x_get_y' && (
            (int) ($payload['buy_quantity'] ?? 0) <= 0
            || (int) ($payload['get_quantity'] ?? 0) <= 0
        )) {
            throw ValidationException::withMessages([
                'buy_quantity' => 'Promo buy X get Y wajib punya nilai buy dan get yang valid.',
            ]);
        }

        $productIds = $this->promoRepository->getProducts($outletId)->pluck('id')->all();
        $categoryIds = $this->promoRepository->getCategories($outletId)->pluck('id')->all();
        $tierIds = $this->promoRepository->getMembershipTiers($outletId)->pluck('id')->all();

        $duplicates = collect($rules)
            ->map(fn (array $rule) => implode(':', [
                $rule['trigger'],
                $rule['reference_id'] ?? '',
                $rule['reference_value'] ?? '',
            ]))
            ->duplicates();

        if ($duplicates->isNotEmpty()) {
            throw ValidationException::withMessages([
                'rules' => 'Trigger promo tidak boleh duplikat.',
            ]);
        }

        foreach ($rules as $index => $rule) {
            $key = 'rules.' . $index;

            if (in_array($rule['trigger'], ['product', 'category', 'member_tier'], true) && blank($rule['reference_id'])) {
                throw ValidationException::withMessages([
                    $key . '.reference_id' => 'Referensi wajib dipilih untuk trigger ini.',
                ]);
            }

            if ($rule['trigger'] === 'product' && !in_array($rule['reference_id'], $productIds, true)) {
                throw ValidationException::withMessages([
                    $key . '.reference_id' => 'Produk pada trigger promo tidak valid untuk outlet ini.',
                ]);
            }

            if ($rule['trigger'] === 'category' && !in_array($rule['reference_id'], $categoryIds, true)) {
                throw ValidationException::withMessages([
                    $key . '.reference_id' => 'Kategori pada trigger promo tidak valid untuk outlet ini.',
                ]);
            }

            if ($rule['trigger'] === 'member_tier' && !in_array($rule['reference_id'], $tierIds, true)) {
                throw ValidationException::withMessages([
                    $key . '.reference_id' => 'Tier membership pada trigger promo tidak valid untuk outlet ini.',
                ]);
            }

            if ($rule['trigger'] === 'payment_method' && !in_array($rule['reference_value'], self::PAYMENT_METHODS, true)) {
                throw ValidationException::withMessages([
                    $key . '.reference_value' => 'Metode bayar promo tidak valid.',
                ]);
            }

            if ($rule['trigger'] === 'time') {
                $hasDay = filled($rule['reference_value']) && in_array($rule['reference_value'], self::DAY_OPTIONS, true);
                $hasHourRange = filled($payload['happy_hour_start'] ?? null) && filled($payload['happy_hour_end'] ?? null);

                if (!$hasDay && !$hasHourRange) {
                    throw ValidationException::withMessages([
                        $key . '.reference_value' => 'Trigger waktu butuh hari aktif atau jam happy hour.',
                    ]);
                }
            }
        }
    }

    protected function assertOwner(?User $user): void
    {
        if (!$user || $user->role?->type !== 'owner') {
            abort(403, 'Menu promo hanya bisa dikelola oleh owner.');
        }
    }

    protected function assertSameOutlet(Promo $promo, User $actor): void
    {
        if ($promo->outlet_id !== $actor->outlet_id) {
            throw ValidationException::withMessages([
                'error' => 'Promo ini tidak berada di outlet aktif Anda.',
            ]);
        }
    }
}
