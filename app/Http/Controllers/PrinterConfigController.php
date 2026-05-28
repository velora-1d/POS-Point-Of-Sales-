<?php

namespace App\Http\Controllers;

use App\Http\Requests\PrinterConfigIndexRequest;
use App\Http\Requests\UpsertPrinterConfigRequest;
use App\Services\PrinterConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class PrinterConfigController extends Controller
{
    public function __construct(
        protected PrinterConfigService $printerConfigService,
    ) {
    }

    public function index(PrinterConfigIndexRequest $request): Response
    {
        $data = $this->printerConfigService->getDashboard(
            $request->user(),
            $request->validated(),
        );

        return Inertia::render('Settings/Printer', [
            'outlets' => $data['outlets'],
            'selectedOutlet' => $data['selectedOutlet'],
            'summary' => $data['summary'],
            'formDefaults' => $data['formDefaults'],
            'printerOptions' => $data['printerOptions'],
            'preview' => $data['preview'],
            'filters' => $data['filters'],
            'access' => $data['access'],
            'success' => session('success'),
        ]);
    }

    public function update(UpsertPrinterConfigRequest $request): RedirectResponse
    {
        $outletId = $this->printerConfigService->saveConfig(
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('settings.printer.index', ['outlet_id' => $outletId])
            ->with('success', 'Konfigurasi printer berhasil disimpan.');
    }

    public function preview(PrinterConfigIndexRequest $request): HttpResponse
    {
        $html = $this->printerConfigService->renderTestPrintHtml(
            $request->user(),
            $request->validated(),
        );

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}
