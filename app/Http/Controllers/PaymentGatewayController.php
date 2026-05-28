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

    public function index(PaymentGatewayIndexRequest $request): Response
    {
        $data = $this->paymentGatewayConfigService->getDashboard(
            $request->user(),
            $request->validated(),
        );

        return Inertia::render('Settings/PaymentGateway', [
            'outlets' => $data['outlets'],
            'selectedOutlet' => $data['selectedOutlet'],
            'summary' => $data['summary'],
            'formDefaults' => $data['formDefaults'],
            'effectiveConfig' => $data['effectiveConfig'],
            'gatewayOptions' => $data['gatewayOptions'],
            'filters' => $data['filters'],
            'success' => session('success'),
        ]);
    }

    public function update(UpsertPaymentGatewayConfigRequest $request): RedirectResponse
    {
        $this->paymentGatewayConfigService->saveConfig(
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('settings.payment-gateway.index', ['outlet_id' => $request->validated('outlet_id')])
            ->with('success', 'Konfigurasi payment gateway berhasil disimpan.');
    }

    public function test(TestPaymentGatewayConnectionRequest $request): RedirectResponse
    {
        $message = $this->paymentGatewayConfigService->testConnection(
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('settings.payment-gateway.index', ['outlet_id' => $request->validated('outlet_id')])
            ->with('success', $message);
    }
}
