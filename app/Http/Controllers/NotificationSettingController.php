<?php

namespace App\Http\Controllers;

use App\Http\Requests\NotificationSettingIndexRequest;
use App\Http\Requests\UpsertNotificationSettingRequest;
use App\Services\NotificationSettingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NotificationSettingController extends Controller
{
    public function __construct(
        protected NotificationSettingService $notificationSettingService,
    ) {
    }

    public function index(NotificationSettingIndexRequest $request): Response
    {
        $data = $this->notificationSettingService->getDashboard(
            $request->user(),
            $request->validated(),
        );

        return Inertia::render('Settings/Notifications', [
            'outlets' => $data['outlets'],
            'selectedOutlet' => $data['selectedOutlet'],
            'summary' => $data['summary'],
            'formDefaults' => $data['formDefaults'],
            'alertOptions' => $data['alertOptions'],
            'snapshots' => $data['snapshots'],
            'filters' => $data['filters'],
            'success' => session('success'),
        ]);
    }

    public function update(UpsertNotificationSettingRequest $request): RedirectResponse
    {
        $outletId = $this->notificationSettingService->saveConfig(
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('settings.notifications.index', ['outlet_id' => $outletId])
            ->with('success', 'Setting notifikasi berhasil disimpan.');
    }
}
