<?php

namespace App\Http\Controllers;

use App\Http\Requests\BackupSecurityIndexRequest;
use App\Http\Requests\UpsertBackupSecuritySettingRequest;
use App\Services\BackupSecurityService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;

class BackupSecurityController extends Controller
{
    public function __construct(
        protected BackupSecurityService $backupSecurityService,
    ) {
    }

    public function index(BackupSecurityIndexRequest $request): Response
    {
        $data = $this->backupSecurityService->getDashboard(
            $request->user(),
            $request->validated(),
        );

        return Inertia::render('Settings/BackupSecurity', [
            'outlets' => $data['outlets'],
            'selectedOutlet' => $data['selectedOutlet'],
            'summary' => $data['summary'],
            'formDefaults' => $data['formDefaults'],
            'backupOptions' => $data['backupOptions'],
            'latestBackup' => $data['latestBackup'],
            'securityPosture' => $data['securityPosture'],
            'activityLogs' => $data['activityLogs'],
            'filters' => $data['filters'],
            'success' => session('success'),
        ]);
    }

    public function update(UpsertBackupSecuritySettingRequest $request): RedirectResponse
    {
        $outletId = $this->backupSecurityService->saveConfig(
            $request->validated(),
            $request->user(),
            $request->ip(),
        );

        return redirect()
            ->route('settings.backup-security.index', ['outlet_id' => $outletId])
            ->with('success', 'Setting backup & keamanan berhasil disimpan.');
    }

    public function download(BackupSecurityIndexRequest $request): StreamedResponse
    {
        $outletId = (string) ($request->validated()['outlet_id'] ?? '');
        $backup = $this->backupSecurityService->buildManualBackup(
            $outletId,
            $request->user(),
            $request->ip(),
        );

        return response()->streamDownload(function () use ($backup) {
            echo $backup['content'];
        }, $backup['filename'], [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }
}
