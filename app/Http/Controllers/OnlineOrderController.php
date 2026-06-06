<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOnlineOrderWebhookRequest;
use App\Services\OnlineOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnlineOrderController extends Controller
{
    public function __construct(
        protected OnlineOrderService $onlineOrderService,
    ) {
    }

    public function index(Request $request): Response
    {
        $data = $this->onlineOrderService->getDashboard(
            $request->user(),
            $request->only(['platform', 'status', 'outlet_id', 'start_date', 'end_date', 'per_page']),
        );

        return Inertia::render('OnlineOrders/Index', [
            'summary' => $data['summary'],
            'orders' => $data['orders'],
            'history' => $data['history'],
            'filters' => $data['filters'],
            'referenceData' => $data['referenceData'],
            'success' => session('success'),
        ]);
    }

    public function storeGofoodWebhook(StoreOnlineOrderWebhookRequest $request): JsonResponse
    {
        $result = $this->onlineOrderService->receiveWebhook('gofood', $request->validated());

        if ($result['created']) {
            try {
                $order = \App\Models\Order::find($result['order_id']);
                if ($order) {
                    $platformName = $order->source === 'gofood' ? 'GoFood' : 'GoFood';
                    $formattedTotal = number_format($order->total_amount, 0, ',', '.');
                    \App\Services\FirebasePushService::sendToActiveCashiers(
                        "Pesanan Online Baru!",
                        "Ada pesanan masuk dari {$platformName} senilai Rp {$formattedTotal}.",
                        [
                            'type' => 'online_order',
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                        ]
                    );
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to send FCM notification for GoFood online order: " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => $result['created']
                ? 'Order GoFood berhasil diterima.'
                : 'Order GoFood sudah pernah diterima sebelumnya.',
            'data' => $result,
        ]);
    }

    public function storeGrabfoodWebhook(StoreOnlineOrderWebhookRequest $request): JsonResponse
    {
        $result = $this->onlineOrderService->receiveWebhook('grabfood', $request->validated());

        if ($result['created']) {
            try {
                $order = \App\Models\Order::find($result['order_id']);
                if ($order) {
                    $platformName = $order->source === 'grabfood' ? 'GrabFood' : 'GrabFood';
                    $formattedTotal = number_format($order->total_amount, 0, ',', '.');
                    \App\Services\FirebasePushService::sendToActiveCashiers(
                        "Pesanan Online Baru!",
                        "Ada pesanan masuk dari {$platformName} senilai Rp {$formattedTotal}.",
                        [
                            'type' => 'online_order',
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                        ]
                    );
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to send FCM notification for GrabFood online order: " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => $result['created']
                ? 'Order GrabFood berhasil diterima.'
                : 'Order GrabFood sudah pernah diterima sebelumnya.',
            'data' => $result,
        ]);
    }
}
