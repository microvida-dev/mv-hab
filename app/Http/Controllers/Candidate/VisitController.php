<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\VisitCancellationReason;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookVisitRequest;
use App\Http\Requests\CancelVisitRequest;
use App\Http\Requests\RescheduleVisitRequest;
use App\Models\Application;
use App\Models\HousingVisit;
use App\Models\VisitSlot;
use App\Services\Visits\VisitBookingService;
use App\Services\Visits\VisitCalendarService;
use App\Services\Visits\VisitCancellationService;
use App\Services\Visits\VisitReschedulingService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class VisitController extends Controller
{
    public function __construct(
        private readonly VisitBookingService $booking,
        private readonly VisitReschedulingService $rescheduling,
        private readonly VisitCancellationService $cancellation,
        private readonly VisitCalendarService $calendar,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', HousingVisit::class);
        $user = $this->authenticatedUser($request);

        return view('candidate.visits.index', [
            'visits' => HousingVisit::query()
                ->forCandidate($user)
                ->with(['application.contest', 'contest', 'housingUnit', 'slot', 'statusHistories.changedBy'])
                ->latest('starts_at')
                ->paginate(10),
            'calendar' => $this->calendar->candidateCalendar($user),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', HousingVisit::class);

        return view('candidate.visits.create', [
            'slots' => VisitSlot::query()->available()->with(['contest', 'housingUnit'])->orderBy('starts_at')->limit(50)->get(),
            'applications' => Application::query()->forUser($this->authenticatedUser($request))->with('contest')->latest()->get(),
            'notice' => 'O agendamento de visita está sujeito à disponibilidade dos serviços municipais e poderá ser alterado ou cancelado por motivos operacionais. A confirmação será apresentada na plataforma e, quando aplicável, enviada por notificação.',
        ]);
    }

    public function store(BookVisitRequest $request): RedirectResponse
    {
        $visit = $this->booking->book($this->authenticatedUser($request), $request->validated());

        return to_route('candidate.visits.show', $visit)->with('success', 'Pedido de visita registado.');
    }

    public function show(HousingVisit $housingVisit): View
    {
        Gate::authorize('view', $housingVisit);
        $housingVisit->load(['application.contest', 'contest', 'housingUnit', 'slot', 'statusHistories.changedBy']);

        return view('candidate.visits.show', [
            'visit' => $housingVisit,
            'notice' => 'O agendamento de visita está sujeito à disponibilidade dos serviços municipais e poderá ser alterado ou cancelado por motivos operacionais. A confirmação será apresentada na plataforma e, quando aplicável, enviada por notificação.',
        ]);
    }

    public function edit(HousingVisit $housingVisit): View
    {
        Gate::authorize('update', $housingVisit);

        return view('candidate.visits.reschedule', [
            'visit' => $housingVisit,
            'slots' => VisitSlot::query()
                ->available()
                ->when($housingVisit->contest_id !== null, fn (Builder $query) => $query->where('contest_id', $housingVisit->contest_id))
                ->when($housingVisit->housing_unit_id !== null, fn (Builder $query) => $query->where('housing_unit_id', $housingVisit->housing_unit_id))
                ->orderBy('starts_at')
                ->limit(50)
                ->get(),
        ]);
    }

    public function reschedule(RescheduleVisitRequest $request, HousingVisit $housingVisit): RedirectResponse
    {
        $slot = VisitSlot::query()->findOrFail($request->integer('new_visit_slot_id'));
        $visit = $this->rescheduling->reschedule($housingVisit, $slot, $this->authenticatedUser($request), $request->validated('reason'));

        return to_route('candidate.visits.show', $visit)->with('success', 'Visita reagendada.');
    }

    public function cancel(CancelVisitRequest $request, HousingVisit $housingVisit): RedirectResponse
    {
        $visit = $this->cancellation->cancel(
            $housingVisit,
            $this->authenticatedUser($request),
            VisitCancellationReason::from((string) $request->validated('cancellation_reason')),
            $request->validated('cancellation_notes'),
        );

        return to_route('candidate.visits.show', $visit)->with('success', 'Visita cancelada.');
    }
}
