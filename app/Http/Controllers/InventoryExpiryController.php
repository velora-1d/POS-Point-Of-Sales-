<?php

namespace App\Http\Controllers;

use App\Http\Requests\HandleInventoryExpiryRequest;
use App\Models\InventoryExpiry;
use App\Services\InventoryExpiryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryExpiryController extends Controller
{
    public function __construct(
        protected InventoryExpiryService $inventoryExpiryService,
    ) {
    }

    public function index(Request $request): Response
    {
        $data = $this->inventoryExpiryService->getReminderSnapshot(
            $request->user(),
            $request->only(['days', 'type', 'status']),
            32,
        );

        return Inertia::render('Expired/Index', [
            'summary' => $data['summary'],
            'items' => $data['items'],
            'upcomingItems' => $data['upcomingItems'],
            'todayItems' => $data['todayItems'],
            'expiredItems' => $data['expiredItems'],
            'filters' => $data['filters'],
            'success' => session('success'),
        ]);
    }

    public function handle(
        HandleInventoryExpiryRequest $request,
        InventoryExpiry $inventoryExpiry,
    ): RedirectResponse {
        $this->inventoryExpiryService->handle(
            $inventoryExpiry,
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('expired-tracking.index')
            ->with('success', 'Reminder expired berhasil ditandai.');
    }
}
