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

    public function barIndex(): Response
    {
        return Inertia::render('Bar/Display', $this->kitchenDisplayService->getBarBoardData(auth()->user()));
    }

    public function updateStatus(UpdateKitchenOrderStatusRequest $request, Order $order): RedirectResponse
    {
        $payload = $request->validated();

        if ($payload['action'] === 'set_estimate') {
            $this->kitchenDisplayService->updateOrderEstimate(
                $order,
                (int) $payload['estimate_minutes'],
                auth()->user(),
            );

            return back()->with('success', 'Estimasi waktu masak berhasil diperbarui.');
        }

        $this->kitchenDisplayService->updateOrderStatus($order, $payload['action'], auth()->user());

        return back()->with('success', 'Status order dapur berhasil diperbarui.');
    }

    public function approveBar(Order $order): RedirectResponse
    {
        $this->kitchenDisplayService->approveBarReady($order, auth()->user());

        return back()->with('success', 'Order berhasil di-approve dan siap disajikan.');
    }
}
