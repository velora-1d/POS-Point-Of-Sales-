<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentGatewayIndexRequest;
use App\Http\Requests\TestPaymentGatewayConnectionRequest;
use App\Http\Requests\UpsertPaymentGatewayConfigRequest;
use App\Services\PaymentGatewayConfigService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PaymentGatewayController extends Controller
{
    public function __construct(
        protected PaymentGatewayConfigService $paymentGatewayConfigService,
    ) {
    }

    public function index(): Response
    {
        $data = $this->paymentGatewayConfigService->getGlobalDashboard();

        return Inertia::render('Settings/PaymentGateway', [
            'effectiveConfig' => $data['effectiveConfig'],
            'success' => session('success'),
        ]);
    }

    public function test(): RedirectResponse
    {
        $message = $this->paymentGatewayConfigService->testGlobalConnection();

        return redirect()
            ->route('settings.payment-gateway.index')
            ->with('success', $message);
    }
}
