<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaintenanceCategoryRequest;
use App\Http\Requests\UpdateMaintenanceCategoryRequest;
use App\Models\MaintenanceCategory;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MaintenanceCategoryController extends Controller
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', MaintenanceCategory::class);

        $categories = $this->municipalScope
            ->maintenanceCategories(
                MaintenanceCategory::query(),
                $this->authenticatedUser($request),
            )
            ->with('parent')
            ->orderBy('sort_order')
            ->paginate(20);

        return view(
            'backoffice.maintenance.categories.index',
            compact('categories'),
        );
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', MaintenanceCategory::class);

        $categories = $this->municipalScope
            ->maintenanceCategories(
                MaintenanceCategory::query(),
                $this->authenticatedUser($request),
            )
            ->orderBy('name')
            ->get();

        return view(
            'backoffice.maintenance.categories.create',
            compact('categories'),
        );
    }

    public function store(
        StoreMaintenanceCategoryRequest $request,
    ): RedirectResponse {
        Gate::authorize('create', MaintenanceCategory::class);

        $actor = $this->authenticatedUser($request);
        $municipalityId = $actor->municipality_id;

        abort_if($municipalityId === null, 403);

        $category = new MaintenanceCategory(
            $request->validated(),
        );

        $category->forceFill([
            'municipality_id' => $municipalityId,
            'is_system' => false,
        ])->save();

        return to_route(
            'backoffice.maintenance.categories.index',
        )->with('success', 'Categoria criada.');
    }

    public function edit(
        Request $request,
        MaintenanceCategory $maintenanceCategory,
    ): View {
        Gate::authorize('update', $maintenanceCategory);

        $categories = $this->municipalScope
            ->maintenanceCategories(
                MaintenanceCategory::query(),
                $this->authenticatedUser($request),
            )
            ->whereKeyNot($maintenanceCategory->id)
            ->orderBy('name')
            ->get();

        return view(
            'backoffice.maintenance.categories.edit',
            compact('maintenanceCategory', 'categories'),
        );
    }

    public function update(
        UpdateMaintenanceCategoryRequest $request,
        MaintenanceCategory $maintenanceCategory,
    ): RedirectResponse {
        Gate::authorize('update', $maintenanceCategory);

        $maintenanceCategory->update(
            $request->validated(),
        );

        return to_route(
            'backoffice.maintenance.categories.index',
        )->with('success', 'Categoria atualizada.');
    }

    public function destroy(
        MaintenanceCategory $maintenanceCategory,
    ): RedirectResponse {
        Gate::authorize('delete', $maintenanceCategory);

        $maintenanceCategory->delete();

        return to_route(
            'backoffice.maintenance.categories.index',
        )->with('success', 'Categoria removida.');
    }
}
