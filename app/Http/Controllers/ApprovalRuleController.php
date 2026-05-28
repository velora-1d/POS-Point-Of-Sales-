<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApprovalRuleIndexRequest;
use App\Http\Requests\UpsertApprovalRuleRequest;
use App\Services\ApprovalRuleService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalRuleController extends Controller
{
    public function __construct(
        protected ApprovalRuleService $approvalRuleService,
    ) {
    }

    public function index(ApprovalRuleIndexRequest $request): Response
    {
        $data = $this->approvalRuleService->getDashboard(
            $request->user(),
            $request->validated(),
        );

        return Inertia::render('Settings/ApprovalRules', [
            'outlets' => $data['outlets'],
            'selectedOutlet' => $data['selectedOutlet'],
            'summary' => $data['summary'],
            'formDefaults' => $data['formDefaults'],
            'filters' => $data['filters'],
            'success' => session('success'),
        ]);
    }

    public function update(UpsertApprovalRuleRequest $request): RedirectResponse
    {
        $outletId = $this->approvalRuleService->saveConfig(
            $request->validated(),
            $request->user(),
            $request->ip(),
        );

        return redirect()
            ->route('settings.approval-rules.index', ['outlet_id' => $outletId])
            ->with('success', 'Approval rules berhasil disimpan.');
    }
}
