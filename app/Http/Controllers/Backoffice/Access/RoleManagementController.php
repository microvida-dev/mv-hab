<?php

namespace App\Http\Controllers\Backoffice\Access;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Access\AssignUserRoleRequest;
use App\Models\Role;
use App\Models\User;
use App\Policies\RoleAssignmentPolicy;
use App\Services\Access\RoleAssignmentService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RoleManagementController extends Controller
{
    public function index(Request $request, RoleAssignmentPolicy $policy): View
    {
        abort_unless($policy->viewAny($this->authenticatedUser($request)), 403);

        return view('backoffice.access.roles.index', [
            'roles' => Role::query()->withCount('users')->with('permissions')->orderBy('label')->get(),
            'users' => User::query()->with('roles')->orderBy('name')->limit(200)->get(),
        ]);
    }

    public function assign(AssignUserRoleRequest $request, User $user, RoleAssignmentService $roles): RedirectResponse
    {
        $role = Role::query()->where('name', $request->validated('role'))->firstOrFail();

        try {
            $roles->assign($this->authenticatedUser($request), $user, $role, $request->validated('justification'));
        } catch (DomainException $exception) {
            return back()->withErrors(['access' => $exception->getMessage()]);
        }

        return back()->with('status', 'Role atribuída com auditoria.');
    }

    public function remove(AssignUserRoleRequest $request, User $user, RoleAssignmentService $roles): RedirectResponse
    {
        $role = Role::query()->where('name', $request->validated('role'))->firstOrFail();

        try {
            $roles->remove($this->authenticatedUser($request), $user, $role, $request->validated('justification'));
        } catch (DomainException $exception) {
            return back()->withErrors(['access' => $exception->getMessage()]);
        }

        return back()->with('status', 'Role removida com auditoria.');
    }
}
