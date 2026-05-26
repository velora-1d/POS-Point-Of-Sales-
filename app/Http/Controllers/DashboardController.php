<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
    ) {
    }

    public function index(Request $request): Response
    {
        $data = $this->dashboardService->getDashboard(
            $request->user(),
            $request->only(['outlet_id']),
        );

        return Inertia::render('Dashboard', [
            'alerts' => $data['alerts'],
            'finance' => $data['finance'],
            'filters' => $data['filters'],
            'referenceData' => $data['referenceData'],
        ]);
    }
}
