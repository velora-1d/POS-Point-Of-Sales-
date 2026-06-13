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
            'effectiveConfig' => $data['effectiveConfig'],
            'formDefaults' => $data['formDefaults'],
            'filters' => $data['filters'],
            'access' => $data['access'],
            'success' => session('success'),
        ]);
    }

    public function update(UpsertPaymentGatewayConfigRequest $request): RedirectResponse
    {
        $outletId = $this->paymentGatewayConfigService->saveConfig(
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('settings.payment-gateway.index', ['outlet_id' => $outletId])
            ->with('success', 'Pengaturan metode pembayaran gateway berhasil disimpan.');
    }

    public function test(UpsertPaymentGatewayConfigRequest $request): RedirectResponse
    {
        $message = $this->paymentGatewayConfigService->testConnection(
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('settings.payment-gateway.index', ['outlet_id' => $request->input('outlet_id')])
            ->with('success', $message);
    }
}
