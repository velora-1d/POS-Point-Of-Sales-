<?php

namespace App\Services;

use App\Models\Outlet;
use App\Models\User;
use App\Repositories\CustomerRepository;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    public function __construct(
        protected CustomerRepository $customerRepository,
    ) {
    }

    public function getCustomerDatabase(?User $user, array $filters = []): array
    {
        $outletId = $user?->outlet_id ?? Outlet::query()->value('id');

        if (!$outletId) {
            throw ValidationException::withMessages([
                'error' => 'Belum ada outlet terdaftar di sistem.',
            ]);
        }

        return [
            'customers' => $this->customerRepository->paginateByOutlet($outletId, $filters),
            'summary' => $this->customerRepository->getSummary($outletId),
            'loyalty' => $this->customerRepository->getLoyaltySummary($outletId),
            'tiers' => $this->customerRepository->getTierBreakdown($outletId),
            'topMembers' => $this->customerRepository->getTopLoyalCustomers($outletId),
            'kasbon' => $this->customerRepository->getKasbonSummary($outletId),
            'topKasbonCustomers' => $this->customerRepository->getTopKasbonCustomers($outletId),
            'filters' => [
                'search' => (string) ($filters['search'] ?? ''),
                'per_page' => (int) ($filters['per_page'] ?? 12),
            ],
        ];
    }
}
