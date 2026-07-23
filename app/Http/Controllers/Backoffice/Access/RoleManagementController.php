<?php

namespace App\Http\Controllers\Backoffice\Access;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Access\AssignUserRoleRequest;
use App\Http\Requests\Backoffice\Access\DeleteRoleRequest;
use App\Http\Requests\Backoffice\Access\DuplicateRoleRequest;
use App\Http\Requests\Backoffice\Access\StoreRoleRequest;
use App\Http\Requests\Backoffice\Access\SyncRolePermissionsRequest;
use App\Http\Requests\Backoffice\Access\ToggleRoleStatusRequest;
use App\Http\Requests\Backoffice\Access\UpdateRoleRequest;
use App\Models\AccessChangeEvent;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Policies\RolePolicy;
use App\Services\Access\RoleAssignmentService;
use App\Services\Access\RoleManagementService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RoleManagementController extends Controller
{
    public function index(Request $request, RolePolicy $policy): View
    {
        abort_unless($policy->viewAny($this->authenticatedUser($request)), 403);

        $roles = Role::query()
            ->withCount(['users', 'permissions'])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $search = '%'.$request->string('q')->trim()->toString().'%';
                $query->where(fn ($inner) => $inner
                    ->where('label', 'like', $search)
                    ->orWhere('name', 'like', $search));
            })
            ->when($request->string('type')->toString() === 'system', fn ($query) => $query->where('is_system', true))
            ->when($request->string('type')->toString() === 'municipal', fn ($query) => $query->where('is_system', false)->where('scope', 'municipal'))
            ->when($request->string('status')->toString() === 'active', fn ($query) => $query->where('is_active', true))
            ->when($request->string('status')->toString() === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderByDesc('is_system')
            ->orderBy('label')
            ->paginate(20)
            ->withQueryString();

        return view('backoffice.access.roles.index', ['roles' => $roles]);
    }

    public function create(Request $request, RolePolicy $policy): View
    {
        abort_unless($policy->create($this->authenticatedUser($request)), 403);

        return view('backoffice.access.roles.create', [
            'permissions' => $this->permissions(),
        ]);
    }

    public function store(StoreRoleRequest $request, RoleManagementService $roles): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $role = $roles->create(
                $this->authenticatedUser($request),
                $validated,
                $this->permissionIds($validated),
                (string) $validated['justification'],
            );
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['role' => $exception->getMessage()]);
        }

        return redirect()->route('backoffice.roles.show', $role)
            ->with('status', 'Perfil municipal criado com auditoria.');
    }

    public function show(Request $request, Role $role, RolePolicy $policy): View
    {
        abort_unless($policy->view($this->authenticatedUser($request), $role), 403);

        return view('backoffice.access.roles.show', [
            'role' => $role->load('permissions')->loadCount('users'),
        ]);
    }

    public function edit(Request $request, Role $role, RolePolicy $policy): View
    {
        abort_unless($policy->update($this->authenticatedUser($request), $role), 403);

        return view('backoffice.access.roles.edit', [
            'role' => $role->load('permissions'),
            'permissions' => $this->permissions(),
        ]);
    }

    public function update(
        UpdateRoleRequest $request,
        Role $role,
        RoleManagementService $roles,
    ): RedirectResponse {
        $validated = $request->validated();

        try {
            $roles->updateDetails(
                $this->authenticatedUser($request),
                $role,
                $validated,
                (string) $validated['justification'],
            );
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['role' => $exception->getMessage()]);
        }

        return redirect()->route('backoffice.roles.show', $role)
            ->with('status', 'Dados do perfil atualizados.');
    }

    public function syncPermissions(
        SyncRolePermissionsRequest $request,
        Role $role,
        RoleManagementService $roles,
    ): RedirectResponse {
        $validated = $request->validated();

        try {
            $roles->synchronizePermissions(
                $this->authenticatedUser($request),
                $role,
                $this->permissionIds($validated),
                (string) $validated['justification'],
            );
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['role' => $exception->getMessage()]);
        }

        return redirect()->route('backoffice.roles.edit', $role)
            ->with('status', 'Permissões do perfil atualizadas.');
    }

    public function duplicate(
        DuplicateRoleRequest $request,
        Role $role,
        RoleManagementService $roles,
    ): RedirectResponse {
        $validated = $request->validated();

        try {
            $copy = $roles->duplicate(
                $this->authenticatedUser($request),
                $role,
                (string) $validated['label'],
                is_string($validated['description'] ?? null) ? $validated['description'] : null,
                (string) $validated['justification'],
            );
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['role' => $exception->getMessage()]);
        }

        return redirect()->route('backoffice.roles.edit', $copy)
            ->with('status', 'Perfil duplicado sem copiar utilizadores.');
    }

    public function activate(
        ToggleRoleStatusRequest $request,
        Role $role,
        RoleManagementService $roles,
    ): RedirectResponse {
        $roles->activate(
            $this->authenticatedUser($request),
            $role,
            $request->validated('justification'),
        );

        return back()->with('status', 'Perfil municipal ativado.');
    }

    public function deactivate(
        ToggleRoleStatusRequest $request,
        Role $role,
        RoleManagementService $roles,
    ): RedirectResponse {
        $roles->deactivate(
            $this->authenticatedUser($request),
            $role,
            $request->validated('justification'),
        );

        return back()->with('status', 'Perfil municipal desativado. As associações foram preservadas.');
    }

    public function users(Request $request, Role $role, RolePolicy $policy): View
    {
        abort_unless($policy->viewUsers($this->authenticatedUser($request), $role), 403);

        return view('backoffice.access.roles.users', [
            'role' => $role->loadCount('users'),
            'users' => $role->users()
                ->with('roles')
                ->orderBy('name')
                ->paginate(20),
        ]);
    }

    public function audit(Request $request, Role $role, RolePolicy $policy): View
    {
        abort_unless($policy->audit($this->authenticatedUser($request), $role), 403);

        return view('backoffice.access.roles.audit', [
            'role' => $role,
            'events' => AccessChangeEvent::query()
                ->with('actor')
                ->where('role_id', $role->id)
                ->latest('occurred_at')
                ->paginate(20),
        ]);
    }

    public function destroy(
        DeleteRoleRequest $request,
        Role $role,
        RoleManagementService $roles,
    ): RedirectResponse {
        try {
            $roles->delete(
                $this->authenticatedUser($request),
                $role,
                $request->validated('justification'),
            );
        } catch (DomainException $exception) {
            return back()->withErrors(['role' => $exception->getMessage()]);
        }

        return redirect()->route('backoffice.roles.index')
            ->with('status', 'Perfil municipal eliminado com auditoria.');
    }

    public function assign(AssignUserRoleRequest $request, User $user, RoleAssignmentService $roles): RedirectResponse
    {
        $role = Role::query()->where('name', $request->validated('role'))->firstOrFail();

        try {
            $roles->assign($this->authenticatedUser($request), $user, $role, $request->validated('justification'));
        } catch (DomainException $exception) {
            return back()->withErrors(['access' => $exception->getMessage()]);
        }

        return back()->with('status', 'Perfil atribuído com auditoria.');
    }

    public function remove(AssignUserRoleRequest $request, User $user, RoleAssignmentService $roles): RedirectResponse
    {
        $role = Role::query()->where('name', $request->validated('role'))->firstOrFail();

        try {
            $roles->remove($this->authenticatedUser($request), $user, $role, $request->validated('justification'));
        } catch (DomainException $exception) {
            return back()->withErrors(['access' => $exception->getMessage()]);
        }

        return back()->with('status', 'Perfil removido com auditoria.');
    }

    /** @return Collection<int, Permission> */
    private function permissions(): Collection
    {
        return Permission::query()
            ->where('name', '!=', '*')
            ->orderBy('module')
            ->orderBy('action')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return list<int>
     */
    private function permissionIds(array $validated): array
    {
        $permissions = $validated['permissions'] ?? [];

        if (! is_array($permissions)) {
            return [];
        }

        return array_values(collect($permissions)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all());
    }
}
