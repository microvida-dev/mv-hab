<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\VisitCancellationReason;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelVisitRequest;
use App\Http\Requests\CompleteVisitRequest;
use App\Http\Requests\RejectVisitRequest;
use App\Models\HousingVisit;
use App\Services\Visits\VisitBookingService;
use App\Services\Visits\VisitCancellationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class HousingVisitController extends Controller
{
    public function __construct(
        private readonly VisitBookingService $booking,
        private readonly VisitCancellationService $cancellation,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAny', HousingVisit::class);

        return view('backoffice.housing-visits.index', [
            'visits' => HousingVisit::query()->with(['candidate', 'application.contest', 'contest', 'housingUnit', 'staff'])->latest('starts_at')->paginate(20),
        ]);
    }

    public function show(HousingVisit $housingVisit): View
    {
        Gate::authorize('view', $housingVisit);
        $housingVisit->load(['candidate', 'application.contest', 'contest', 'housingUnit', 'staff', 'slot', 'statusHistories.changedBy']);

        return view('backoffice.housing-visits.show', ['visit' => $housingVisit]);
    }

    public function confirm(Request $request, HousingVisit $housingVisit): RedirectResponse
    {
        Gate::authorize('approve', $housingVisit);
        $visit = $this->booking->confirm($housingVisit, $this->authenticatedUser($request));

        return to_route('backoffice.housing-visits.show', $visit)->with('success', 'Visita confirmada.');
    }

    public function complete(CompleteVisitRequest $request, HousingVisit $housingVisit): RedirectResponse
    {
        $visit = $this->booking->complete($housingVisit, $this->authenticatedUser($request), $request->validated('staff_notes'));

        return to_route('backoffice.housing-visits.show', $visit)->with('success', 'Visita concluída.');
    }

    public function noShow(CompleteVisitRequest $request, HousingVisit $housingVisit): RedirectResponse
    {
        $visit = $this->booking->markNoShow($housingVisit, $this->authenticatedUser($request), (string) $request->validated('staff_notes'));

        return to_route('backoffice.housing-visits.show', $visit)->with('success', 'Falta de comparência registada.');
    }

    public function cancel(CancelVisitRequest $request, HousingVisit $housingVisit): RedirectResponse
    {
        $visit = $this->cancellation->cancel(
            $housingVisit,
            $this->authenticatedUser($request),
            VisitCancellationReason::from((string) $request->validated('cancellation_reason')),
            $request->validated('cancellation_notes'),
        );

        return to_route('backoffice.housing-visits.show', $visit)->with('success', 'Visita cancelada.');
    }

    public function reject(RejectVisitRequest $request, HousingVisit $housingVisit): RedirectResponse
    {
        $visit = $this->booking->reject($housingVisit, $this->authenticatedUser($request), $request->validated('reason'));

        return to_route('backoffice.housing-visits.show', $visit)->with('success', 'Visita recusada.');
    }
}
