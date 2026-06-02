<?php

namespace App\Repositories;

use App\Models\MembershipTier;
use Illuminate\Support\Collection;

class MembershipTierRepository
{
    public function getAllByOutlet(string $outletId): Collection
    {
        return MembershipTier::query()
            ->where('outlet_id', $outletId)
            ->orderBy('point_threshold')
            ->get();
    }

    public function create(array $data): MembershipTier
    {
        return MembershipTier::create($data);
    }

    public function update(MembershipTier $membershipTier, array $data): bool
    {
        return $membershipTier->update($data);
    }

    public function delete(MembershipTier $membershipTier): bool
    {
        return $membershipTier->delete();
    }
}
