<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMembershipTierRequest;
use App\Http\Requests\UpdateMembershipTierRequest;
use App\Models\MembershipTier;
use App\Services\MembershipTierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MembershipTierController extends Controller
{
    public function __construct(
        protected MembershipTierService $membershipTierService,
    ) {
    }

    public function index(Request $request): Response
    {
        $data = $this->membershipTierService->getTiersList($request->user());

        return Inertia::render('Settings/MembershipTiers', [
            'tiers' => $data['tiers'],
            'outlet_id' => $data['outlet_id'],
        ]);
    }

    public function store(StoreMembershipTierRequest $request): RedirectResponse
    {
        $this->membershipTierService->createTier(
            $request->user(),
            $request->validated()
        );

        return redirect()->route('settings.membership-tiers.index')
            ->with('success', 'Tier member baru berhasil ditambahkan.');
    }

    public function update(UpdateMembershipTierRequest $request, MembershipTier $membershipTier): RedirectResponse
    {
        $this->membershipTierService->updateTier(
            $request->user(),
            $membershipTier,
            $request->validated()
        );

        return redirect()->route('settings.membership-tiers.index')
            ->with('success', 'Data tier member berhasil diperbarui.');
    }

    public function destroy(Request $request, MembershipTier $membershipTier): RedirectResponse
    {
        $this->membershipTierService->deleteTier(
            $request->user(),
            $membershipTier
        );

        return redirect()->route('settings.membership-tiers.index')
            ->with('success', 'Tier member berhasil dihapus.');
    }
}
