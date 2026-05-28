<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkRegenerateTableQrRequest;
use App\Http\Requests\TableQrConfigIndexRequest;
use App\Http\Requests\UpsertTableQrConfigRequest;
use App\Services\TableQrConfigService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TableQrConfigController extends Controller
{
    public function __construct(
        protected TableQrConfigService $tableQrConfigService,
    ) {
    }

    public function index(TableQrConfigIndexRequest $request): Response
    {
        $data = $this->tableQrConfigService->getDashboard(
            $request->user(),
            $request->validated(),
        );

        return Inertia::render('Settings/TableQr', [
            'outlets' => $data['outlets'],
            'selectedOutlet' => $data['selectedOutlet'],
            'summary' => $data['summary'],
            'formDefaults' => $data['formDefaults'],
            'preview' => $data['preview'],
            'regeneration' => $data['regeneration'],
            'qrOptions' => $data['qrOptions'],
            'filters' => $data['filters'],
            'success' => session('success'),
        ]);
    }

    public function update(UpsertTableQrConfigRequest $request): RedirectResponse
    {
        $outletId = $this->tableQrConfigService->saveConfig(
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('settings.table-qr.index', ['outlet_id' => $outletId])
            ->with('success', 'Konfigurasi QR meja berhasil disimpan.');
    }

    public function regenerate(BulkRegenerateTableQrRequest $request): RedirectResponse
    {
        $result = $this->tableQrConfigService->bulkRegenerate(
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('settings.table-qr.index', ['outlet_id' => $result['outlet_id']])
            ->with('success', 'Bulk regenerate QR selesai untuk ' . $result['count'] . ' meja aktif.');
    }
}
