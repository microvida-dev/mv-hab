<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelKeyHandoverAppointmentRequest;
use App\Http\Requests\CompleteKeyHandoverAppointmentRequest;
use App\Http\Requests\StoreKeyHandoverAppointmentRequest;
use App\Http\Requests\UpdateKeyHandoverAppointmentRequest;
use App\Models\KeyHandoverAppointment;
use App\Models\WinnerRegistration;
use App\Services\KeyHandover\KeyHandoverAppointmentService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class KeyHandoverAppointmentController extends Controller
{
    public function __construct(
        private readonly KeyHandoverAppointmentService $appointments,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyBackoffice', KeyHandoverAppointment::class);
        $actor = $this->authenticatedUser($request);

        return view('backoffice.key-handovers.index', [
            'appointments' => $this->municipalScope
                ->keyHandoverAppointments(KeyHandoverAppointment::query(), $actor)
                ->with(['candidate', 'housingUnit'])
                ->latest()
                ->paginate(25),
            'winners' => $this->municipalScope
                ->winnerRegistrations(WinnerRegistration::query(), $actor)
                ->with(['candidate', 'housingUnit'])
                ->latest()
                ->get(),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('createBackoffice', KeyHandoverAppointment::class);

        return view('backoffice.key-handovers.create', [
            'winners' => $this->municipalScope
                ->winnerRegistrations(
                    WinnerRegistration::query(),
                    $this->authenticatedUser($request),
                )
                ->with(['candidate', 'housingUnit'])
                ->latest()
                ->get(),
        ]);
    }

    public function store(StoreKeyHandoverAppointmentRequest $request): RedirectResponse
    {
        Gate::authorize('scheduleBackoffice', KeyHandoverAppointment::class);

        /** @var WinnerRegistration $winner */
        $winner = $this->municipalScope
            ->winnerRegistrations(
                WinnerRegistration::query(),
                $this->authenticatedUser($request),
            )
            ->findOrFail((int) $request->validated('winner_registration_id'));
        $appointment = $this->appointments->schedule($winner, $request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.key-handovers.show', $appointment)->with('success', 'Entrega de chaves agendada.');
    }

    public function show(KeyHandoverAppointment $keyHandoverAppointment): View
    {
        Gate::authorize('viewBackoffice', $keyHandoverAppointment);

        $keyHandoverAppointment->load(['winnerRegistration', 'candidate', 'housingUnit']);

        return view('backoffice.key-handovers.show', compact('keyHandoverAppointment'));
    }

    public function update(UpdateKeyHandoverAppointmentRequest $request, KeyHandoverAppointment $keyHandoverAppointment): RedirectResponse
    {
        Gate::authorize('updateBackoffice', $keyHandoverAppointment);

        $this->appointments->update($keyHandoverAppointment, $request->validated(), $this->authenticatedUser($request));

        return back()->with('success', 'Entrega de chaves reagendada.');
    }

    public function complete(CompleteKeyHandoverAppointmentRequest $request, KeyHandoverAppointment $keyHandoverAppointment): RedirectResponse
    {
        Gate::authorize('completeBackoffice', $keyHandoverAppointment);

        $this->appointments->complete($keyHandoverAppointment, $this->authenticatedUser($request), $request->validated('internal_notes'));

        return back()->with('success', 'Entrega de chaves concluída.');
    }

    public function cancel(CancelKeyHandoverAppointmentRequest $request, KeyHandoverAppointment $keyHandoverAppointment): RedirectResponse
    {
        Gate::authorize('cancelBackoffice', $keyHandoverAppointment);

        $this->appointments->cancel($keyHandoverAppointment, $this->authenticatedUser($request), (string) $request->validated('reason'));

        return back()->with('success', 'Entrega de chaves cancelada.');
    }
}
