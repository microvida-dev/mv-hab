<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelPropertyInspectionRequest;
use App\Http\Requests\CompletePropertyInspectionRequest;
use App\Http\Requests\StorePropertyInspectionRequest;
use App\Http\Requests\UpdatePropertyInspectionRequest;
use App\Http\Requests\ValidatePropertyInspectionRequest;
use App\Models\HousingUnit;
use App\Models\InspectionChecklistTemplate;
use App\Models\PropertyInspection;
use App\Services\Inspections\PropertyInspectionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PropertyInspectionController extends Controller
{
    public function __construct(private readonly PropertyInspectionService $inspections) {}

    public function index(): View
    {
        Gate::authorize('viewAny', PropertyInspection::class);
        $inspections = PropertyInspection::query()->with(['housingUnit', 'leaseContract.candidate', 'inspector'])->latest()->paginate(20);

        return view('backoffice.inspections.index', compact('inspections'));
    }

    public function create(): View
    {
        Gate::authorize('create', PropertyInspection::class);
        $housingUnits = HousingUnit::query()->orderBy('code')->get(['id', 'code', 'address']);
        $templates = InspectionChecklistTemplate::query()->where('is_active', true)->orderBy('name')->get();

        return view('backoffice.inspections.create', compact('housingUnits', 'templates'));
    }

    public function store(StorePropertyInspectionRequest $request): RedirectResponse
    {
        Gate::authorize('create', PropertyInspection::class);
        $inspection = $this->inspections->store($this->authenticatedUser($request), $request->validated());

        return to_route('backoffice.inspections.show', $inspection)->with('success', 'Vistoria criada.');
    }

    public function show(PropertyInspection $propertyInspection): View
    {
        Gate::authorize('view', $propertyInspection);
        $propertyInspection->load(['housingUnit', 'leaseContract.candidate', 'inspector', 'items', 'attachments', 'report']);

        return view('backoffice.inspections.show', compact('propertyInspection'));
    }

    public function edit(PropertyInspection $propertyInspection): View
    {
        Gate::authorize('update', $propertyInspection);
        $housingUnits = HousingUnit::query()->orderBy('code')->get(['id', 'code', 'address']);
        $templates = InspectionChecklistTemplate::query()->where('is_active', true)->orderBy('name')->get();

        return view('backoffice.inspections.edit', compact('propertyInspection', 'housingUnits', 'templates'));
    }

    public function update(UpdatePropertyInspectionRequest $request, PropertyInspection $propertyInspection): RedirectResponse
    {
        Gate::authorize('update', $propertyInspection);
        $propertyInspection->update($request->validated());

        return to_route('backoffice.inspections.show', $propertyInspection)->with('success', 'Vistoria atualizada.');
    }

    public function start(Request $request, PropertyInspection $propertyInspection): RedirectResponse
    {
        Gate::authorize('update', $propertyInspection);
        $this->inspections->start($propertyInspection, $this->authenticatedUser($request));

        return back()->with('success', 'Vistoria iniciada.');
    }

    public function complete(CompletePropertyInspectionRequest $request, PropertyInspection $propertyInspection): RedirectResponse
    {
        Gate::authorize('update', $propertyInspection);
        $this->inspections->complete($propertyInspection, $this->authenticatedUser($request), $request->validated());

        return back()->with('success', 'Vistoria concluída.');
    }

    public function validateInspection(ValidatePropertyInspectionRequest $request, PropertyInspection $propertyInspection): RedirectResponse
    {
        Gate::authorize('approve', $propertyInspection);
        $this->inspections->validate($propertyInspection, $this->authenticatedUser($request));

        return back()->with('success', 'Vistoria validada.');
    }

    public function close(Request $request, PropertyInspection $propertyInspection): RedirectResponse
    {
        Gate::authorize('update', $propertyInspection);
        $this->inspections->close($propertyInspection, $this->authenticatedUser($request));

        return back()->with('success', 'Vistoria fechada.');
    }

    public function cancel(CancelPropertyInspectionRequest $request, PropertyInspection $propertyInspection): RedirectResponse
    {
        Gate::authorize('update', $propertyInspection);
        $this->inspections->cancel($propertyInspection, $this->authenticatedUser($request));

        return back()->with('success', 'Vistoria cancelada.');
    }
}
