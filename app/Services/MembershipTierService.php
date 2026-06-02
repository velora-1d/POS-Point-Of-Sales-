<?php

namespace App\Services;

use App\Models\MembershipTier;
use App\Models\User;
use App\Models\Outlet;
use App\Repositories\MembershipTierRepository;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MembershipTierService
{
    public function __construct(
        protected MembershipTierRepository $membershipTierRepository,
    ) {
    }

    public function getTiersList(?User $user): array
    {
        $outletId = $user?->outlet_id ?? Outlet::query()->value('id');

        if (!$outletId) {
            throw ValidationException::withMessages([
                'error' => 'Belum ada outlet terdaftar di sistem.',
            ]);
        }

        return [
            'tiers' => $this->membershipTierRepository->getAllByOutlet($outletId),
            'outlet_id' => $outletId,
        ];
    }

    public function createTier(?User $user, array $data): MembershipTier
    {
        $outletId = $user?->outlet_id ?? Outlet::query()->value('id');

        if (!$outletId) {
            throw ValidationException::withMessages([
                'error' => 'Belum ada outlet terdaftar di sistem.',
            ]);
        }

        $data['outlet_id'] = $outletId;
        
        // Generate tier slug if not set
        if (empty($data['tier'])) {
            $data['tier'] = Str::slug($data['name']);
        } else {
            $data['tier'] = Str::slug($data['tier']);
        }

        // Cek duplikasi tier slug di outlet ini
        $exists = MembershipTier::query()
            ->where('outlet_id', $outletId)
            ->where('tier', $data['tier'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'tier' => 'Identifier tier dengan nama/kode tersebut sudah terdaftar di outlet ini.',
            ]);
        }

        return $this->membershipTierRepository->create($data);
    }

    public function updateTier(?User $user, MembershipTier $membershipTier, array $data): bool
    {
        $outletId = $user?->outlet_id ?? Outlet::query()->value('id');

        if (empty($data['tier'])) {
            $data['tier'] = Str::slug($data['name']);
        } else {
            $data['tier'] = Str::slug($data['tier']);
        }

        // Cek duplikasi tier slug di outlet ini (kecuali tier ini sendiri)
        $exists = MembershipTier::query()
            ->where('outlet_id', $outletId)
            ->where('tier', $data['tier'])
            ->where('id', '!=', $membershipTier->id)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'tier' => 'Identifier tier dengan nama/kode tersebut sudah terdaftar di outlet ini.',
            ]);
        }

        return $this->membershipTierRepository->update($membershipTier, $data);
    }

    public function deleteTier(?User $user, MembershipTier $membershipTier): bool
    {
        // Cek apakah ada membership aktif yang menggunakan tier ini
        $hasMembers = $membershipTier->memberships()->exists();
        if ($hasMembers) {
            throw ValidationException::withMessages([
                'error' => 'Tier tidak bisa dihapus karena masih digunakan oleh beberapa pelanggan aktif.',
            ]);
        }

        return $this->membershipTierRepository->delete($membershipTier);
    }
}
