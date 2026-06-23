<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInspectionChecklistTemplateRequest;
use App\Http\Requests\UpdateInspectionChecklistTemplateRequest;
use App\Models\InspectionChecklistTemplate;
use App\Services\Inspections\InspectionTemplateService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class InspectionChecklistTemplateController extends Controller
{
    public function __construct(private readonly InspectionTemplateService $templates) {}

    public function index(): View
    {
        Gate::authorize('viewAny', InspectionChecklistTemplate::class);
        $templates = InspectionChecklistTemplate::query()->with('items')->latest()->paginate(20);

        return view('backoffice.inspections.templates.index', compact('templates'));
    }

    public function create(): View
    {
        Gate::authorize('create', InspectionChecklistTemplate::class);

        return view('backoffice.inspections.templates.create');
    }

    public function store(StoreInspectionChecklistTemplateRequest $request): RedirectResponse
    {
        Gate::authorize('create', InspectionChecklistTemplate::class);
        $this->templates->store($this->authenticatedUser($request), $request->validated());

        return to_route('backoffice.inspections.templates.index')->with('success', 'Template criado.');
    }

    public function edit(InspectionChecklistTemplate $inspectionChecklistTemplate): View
    {
        Gate::authorize('update', $inspectionChecklistTemplate);

        return view('backoffice.inspections.templates.edit', compact('inspectionChecklistTemplate'));
    }

    public function update(UpdateInspectionChecklistTemplateRequest $request, InspectionChecklistTemplate $inspectionChecklistTemplate): RedirectResponse
    {
        Gate::authorize('update', $inspectionChecklistTemplate);
        $inspectionChecklistTemplate->update($request->validated());

        return to_route('backoffice.inspections.templates.index')->with('success', 'Template atualizado.');
    }
}
