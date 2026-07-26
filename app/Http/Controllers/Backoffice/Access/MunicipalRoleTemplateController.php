<?php

namespace App\Http\Controllers\Backoffice\Access;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Policies\RolePolicy;
use App\Services\Access\MunicipalRoleTemplateRegistry;
use App\Services\Access\PermissionCatalogService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MunicipalRoleTemplateController extends Controller
{
    public function index(
        Request $request,
        RolePolicy $policy,
        MunicipalRoleTemplateRegistry $templates,
    ): View {
        abort_unless($policy->viewAny($this->authenticatedUser($request)), 403);

        return view('backoffice.access.role-templates.index', [
            'templates' => $templates->all(),
        ]);
    }

    public function create(
        Request $request,
        string $template,
        RolePolicy $policy,
        MunicipalRoleTemplateRegistry $templates,
        PermissionCatalogService $permissions,
    ): View|RedirectResponse {
        abort_unless($policy->create($this->authenticatedUser($request)), 403);

        try {
            $resolved = $templates->resolve($template);
        } catch (DomainException $exception) {
            return redirect()->route('backoffice.role-templates.index')
                ->withErrors(['template' => $exception->getMessage()]);
        }

        return view('backoffice.access.roles.create', [
            'permissionGroups' => $permissions->grouped(),
            'roleDraft' => new Role([
                'label' => $resolved['label'],
                'description' => $resolved['description'],
            ]),
            'template' => $resolved,
        ]);
    }
}
