<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignRbacUserRoleRequest;
use App\Http\Requests\RbacIndexRequest;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\RbacService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RbacController extends Controller
{
    public function __construct(
        protected RbacService $rbacService,
    ) {
    }

    public function index(RbacIndexRequest $request): Response
    {
        $data = $this->rbacService->getDashboard(
            $request->user(),
            $request->validated(),
        );

        return Inertia::render('Settings/Rbac', [
            'outlets' => $data['outlets'],
            'selectedOutlet' => $data['selectedOutlet'],
            'summary' => $data['summary'],
            'roles' => $data['roles'],
            'employees' => $data['employees'],
            'permissionGroups' => $data['permissionGroups'],
            'roleTypeOptions' => $data['roleTypeOptions'],
            'defaultPermissionMatrix' => $data['defaultPermissionMatrix'],
            'permissionMatrix' => $data['permissionMatrix'],
            'filters' => $data['filters'],
            'success' => session('success'),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->rbacService->createRole(
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('settings.rbac.index', ['outlet_id' => $request->validated('outlet_id')])
            ->with('success', 'Role baru berhasil ditambahkan.');
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->rbacService->updateRole(
            $role,
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('settings.rbac.index', ['outlet_id' => $request->validated('outlet_id')])
            ->with('success', 'Role berhasil diperbarui.');
    }

    public function assignUserRole(AssignRbacUserRoleRequest $request, User $employee): RedirectResponse
    {
        $this->rbacService->assignUserRole(
            $employee,
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('settings.rbac.index', ['outlet_id' => $request->validated('outlet_id')])
            ->with('success', 'Role user berhasil diperbarui.');
    }

    public function saveMatrix(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'outlet_id'              => ['required', 'string'],
            'roles'                  => ['required', 'array'],
            'roles.*.role_id'        => ['required', 'string'],
            'roles.*.permissions'    => ['sometimes', 'array'],
        ]);

        $this->rbacService->saveMatrix($validated, $request->user());

        return redirect()
            ->route('settings.rbac.index', ['outlet_id' => $validated['outlet_id']])
            ->with('success', 'Matriks permission berhasil disimpan.');
    }
}
