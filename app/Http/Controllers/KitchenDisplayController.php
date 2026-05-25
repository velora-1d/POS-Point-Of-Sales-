<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateKitchenOrderStatusRequest;
use App\Models\Order;
use App\Services\KitchenDisplayService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class KitchenDisplayController extends Controller
{
    public function __construct(
        protected KitchenDisplayService $kitchenDisplayService
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Kitchen/Display', $this->kitchenDisplayService->getBoardData(auth()->user()));
    }

    public function updateStatus(UpdateKitchenOrderStatusRequest $request, Order $order): RedirectResponse
    {
        $this->kitchenDisplayService->updateOrderStatus($order, $request->validated()['action'], auth()->user());

        return back()->with('success', 'Status order dapur berhasil diperbarui.');
    }
}
