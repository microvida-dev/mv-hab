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
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PropertyInspectionController extends Controller
{
    public function __construct(
        private readonly PropertyInspectionService $inspections,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize(
            'viewAnyBackoffice',
            PropertyInspection::class,
        );

        $inspections = $this->municipalScope
            ->propertyInspections(
                PropertyInspection::query(),
                $this->authenticatedUser($request),
            )
            ->with([
                'housingUnit',
                'leaseContract.candidate',
                'inspector',
            ])
            ->latest()
            ->paginate(20);

        return view(
            'backoffice.inspections.index',
            compact('inspections'),
        );
    }

    public function create(Request $request): View
    {
        Gate::authorize(
            'createBackoffice',
            PropertyInspection::class,
        );

        $actor = $this->authenticatedUser($request);

        $housingUnits = $this->municipalScope
            ->housingUnits(HousingUnit::query(), $actor)
            ->orderBy('code')
            ->get(['id', 'code', 'address']);

        $templates = $this->municipalScope
            ->inspectionChecklistTemplates(
                InspectionChecklistTemplate::query(),
                $actor,
            )
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view(
            'backoffice.inspections.create',
            compact('housingUnits', 'templates'),
        );
    }

    public function store(
        StorePropertyInspectionRequest $request,
    ): RedirectResponse {
        Gate::authorize(
            'createBackoffice',
            PropertyInspection::class,
        );

        $inspection = $this->inspections->store(
            $this->authenticatedUser($request),
            $request->validated(),
        );

        return to_route(
            'backoffice.inspections.show',
            $inspection,
        )->with('success', 'Vistoria criada.');
    }

    public function show(
        PropertyInspection $propertyInspection,
    ): View {
        Gate::authorize(
            'viewBackoffice',
            $propertyInspection,
        );

        $propertyInspection->load([
            'housingUnit',
            'leaseContract.candidate',
            'inspector',
            'items',
            'attachments',
            'report',
        ]);

        return view(
            'backoffice.inspections.show',
            compact('propertyInspection'),
        );
    }

    public function edit(
        Request $request,
        PropertyInspection $propertyInspection,
    ): View {
        Gate::authorize(
            'updateBackoffice',
            $propertyInspection,
        );

        $actor = $this->authenticatedUser($request);

        $housingUnits = $this->municipalScope
            ->housingUnits(HousingUnit::query(), $actor)
            ->orderBy('code')
            ->get(['id', 'code', 'address']);

        $templates = $this->municipalScope
            ->inspectionChecklistTemplates(
                InspectionChecklistTemplate::query(),
                $actor,
            )
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view(
            'backoffice.inspections.edit',
            compact(
                'propertyInspection',
                'housingUnits',
                'templates',
            ),
        );
    }

    public function update(
        UpdatePropertyInspectionRequest $request,
        PropertyInspection $propertyInspection,
    ): RedirectResponse {
        Gate::authorize(
            'updateBackoffice',
            $propertyInspection,
        );

        $propertyInspection->update(
            $request->validated(),
        );

        return to_route(
            'backoffice.inspections.show',
            $propertyInspection,
        )->with('success', 'Vistoria atualizada.');
    }

    public function start(
        Request $request,
        PropertyInspection $propertyInspection,
    ): RedirectResponse {
        Gate::authorize(
            'startBackoffice',
            $propertyInspection,
        );

        $this->inspections->start(
            $propertyInspection,
            $this->authenticatedUser($request),
        );

        return back()->with(
            'success',
            'Vistoria iniciada.',
        );
    }

    public function complete(
        CompletePropertyInspectionRequest $request,
        PropertyInspection $propertyInspection,
    ): RedirectResponse {
        Gate::authorize(
            'completeBackoffice',
            $propertyInspection,
        );

        $this->inspections->complete(
            $propertyInspection,
            $this->authenticatedUser($request),
            $request->validated(),
        );

        return back()->with(
            'success',
            'Vistoria concluída.',
        );
    }

    public function validateInspection(
        ValidatePropertyInspectionRequest $request,
        PropertyInspection $propertyInspection,
    ): RedirectResponse {
        Gate::authorize(
            'validateBackoffice',
            $propertyInspection,
        );

        $this->inspections->validate(
            $propertyInspection,
            $this->authenticatedUser($request),
        );

        return back()->with(
            'success',
            'Vistoria validada.',
        );
    }

    public function close(
        Request $request,
        PropertyInspection $propertyInspection,
    ): RedirectResponse {
        Gate::authorize(
            'closeBackoffice',
            $propertyInspection,
        );

        $this->inspections->close(
            $propertyInspection,
            $this->authenticatedUser($request),
        );

        return back()->with(
            'success',
            'Vistoria fechada.',
        );
    }

    public function cancel(
        CancelPropertyInspectionRequest $request,
        PropertyInspection $propertyInspection,
    ): RedirectResponse {
        Gate::authorize(
            'cancelBackoffice',
            $propertyInspection,
        );

        $this->inspections->cancel(
            $propertyInspection,
            $this->authenticatedUser($request),
        );

        return back()->with(
            'success',
            'Vistoria cancelada.',
        );
    }
}
