<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertOnlineOrderIntegrationRequest;
use App\Services\OnlineOrderIntegrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnlineOrderIntegrationController extends Controller
{
    public function __construct(
        protected OnlineOrderIntegrationService $onlineOrderIntegrationService,
    ) {
    }

    public function index(Request $request): Response
    {
        $data = $this->onlineOrderIntegrationService->getDashboard($request->user());

        return Inertia::render('Settings/OnlineIntegrations', [
            'summary' => $data['summary'],
            'platforms' => $data['platforms'],
            'success' => session('success'),
        ]);
    }

    public function update(UpsertOnlineOrderIntegrationRequest $request, string $platform): RedirectResponse
    {
        $this->onlineOrderIntegrationService->savePlatform(
            $platform,
            $request->validated(),
            $request->user(),
            $request->ip(),
        );

        return redirect()
            ->route('settings.online-integrations.index')
            ->with('success', 'Konfigurasi integrasi ' . strtoupper($platform) . ' berhasil disimpan.');
    }
}
