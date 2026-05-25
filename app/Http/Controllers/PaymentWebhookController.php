<?php

namespace App\Http\Controllers;

use App\Http\Requests\PakasirWebhookRequest;
use App\Services\OrderPaymentService;
use Illuminate\Http\JsonResponse;

class PaymentWebhookController extends Controller
{
    public function __construct(
        protected OrderPaymentService $orderPaymentService,
    ) {
    }

    public function handlePakasir(PakasirWebhookRequest $request): JsonResponse
    {
        $this->orderPaymentService->handlePakasirWebhook($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Webhook payment diproses.',
        ]);
    }
}
