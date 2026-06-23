<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaintenanceCategoryRequest;
use App\Http\Requests\UpdateMaintenanceCategoryRequest;
use App\Models\MaintenanceCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class MaintenanceCategoryController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', MaintenanceCategory::class);
        $categories = MaintenanceCategory::query()->with('parent')->orderBy('sort_order')->paginate(20);

        return view('backoffice.maintenance.categories.index', compact('categories'));
    }

    public function create(): View
    {
        Gate::authorize('create', MaintenanceCategory::class);
        $categories = MaintenanceCategory::query()->orderBy('name')->get();

        return view('backoffice.maintenance.categories.create', compact('categories'));
    }

    public function store(StoreMaintenanceCategoryRequest $request): RedirectResponse
    {
        Gate::authorize('create', MaintenanceCategory::class);
        MaintenanceCategory::query()->create($request->validated());

        return to_route('backoffice.maintenance.categories.index')->with('success', 'Categoria criada.');
    }

    public function edit(MaintenanceCategory $maintenanceCategory): View
    {
        Gate::authorize('update', $maintenanceCategory);
        $categories = MaintenanceCategory::query()->whereKeyNot($maintenanceCategory->id)->orderBy('name')->get();

        return view('backoffice.maintenance.categories.edit', compact('maintenanceCategory', 'categories'));
    }

    public function update(UpdateMaintenanceCategoryRequest $request, MaintenanceCategory $maintenanceCategory): RedirectResponse
    {
        Gate::authorize('update', $maintenanceCategory);
        $maintenanceCategory->update($request->validated());

        return to_route('backoffice.maintenance.categories.index')->with('success', 'Categoria atualizada.');
    }

    public function destroy(MaintenanceCategory $maintenanceCategory): RedirectResponse
    {
        Gate::authorize('delete', $maintenanceCategory);
        $maintenanceCategory->delete();

        return to_route('backoffice.maintenance.categories.index')->with('success', 'Categoria removida.');
    }
}
