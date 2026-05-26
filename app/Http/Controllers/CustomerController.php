<?php

namespace App\Http\Controllers;

use App\Services\CustomerService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function __construct(
        protected CustomerService $customerService,
    ) {
    }

    public function index(Request $request): Response
    {
        $data = $this->customerService->getCustomerDatabase(
            $request->user(),
            $request->only(['search', 'per_page']),
        );

        return Inertia::render('Customers/Index', [
            'customers' => $data['customers'],
            'summary' => $data['summary'],
            'loyalty' => $data['loyalty'],
            'tiers' => $data['tiers'],
            'topMembers' => $data['topMembers'],
            'kasbon' => $data['kasbon'],
            'topKasbonCustomers' => $data['topKasbonCustomers'],
            'filters' => $data['filters'],
        ]);
    }
}
