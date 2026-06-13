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

    public function updateFcmToken(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $user = $request->user();
        $user->update([
            'fcm_token' => $request->token,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Token FCM berhasil diperbarui.',
        ]);
    }

    public function testPush(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if (!$user->fcm_token) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal: Perangkat Anda belum terdaftar dengan FCM Token di server. Silakan aktifkan/izinkan notifikasi browser terlebih dahulu.',
            ], 400);
        }

        $success = \App\Services\FirebasePushService::sendPush(
            $user->fcm_token,
            'Tes Notifikasi Firebase',
            'Halo ' . $user->name . '! Ini adalah push notifikasi pengujian dari server POS Mentai.',
            [
                'type' => 'test_push',
                'action_url' => route('dashboard'),
            ]
        );

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Tes push notifikasi berhasil dikirim ke perangkat Anda!',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal mengirim push notifikasi. Silakan periksa file credentials.json dan log error server.',
        ], 500);
    }
}
