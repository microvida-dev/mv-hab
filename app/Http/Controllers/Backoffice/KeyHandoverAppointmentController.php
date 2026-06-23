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
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class KeyHandoverAppointmentController extends Controller
{
    public function __construct(private readonly KeyHandoverAppointmentService $appointments) {}

    public function index(): View
    {
        Gate::authorize('viewAny', KeyHandoverAppointment::class);

        return view('backoffice.key-handovers.index', [
            'appointments' => KeyHandoverAppointment::query()->with(['candidate', 'housingUnit'])->latest()->paginate(25),
            'winners' => WinnerRegistration::query()->with(['candidate', 'housingUnit'])->latest()->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', KeyHandoverAppointment::class);

        return view('backoffice.key-handovers.create', [
            'winners' => WinnerRegistration::query()->with(['candidate', 'housingUnit'])->latest()->get(),
        ]);
    }

    public function store(StoreKeyHandoverAppointmentRequest $request): RedirectResponse
    {
        Gate::authorize('create', KeyHandoverAppointment::class);

        /** @var WinnerRegistration $winner */
        $winner = WinnerRegistration::query()->findOrFail((int) $request->validated('winner_registration_id'));
        $appointment = $this->appointments->schedule($winner, $request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.key-handovers.show', $appointment)->with('success', 'Entrega de chaves agendada.');
    }

    public function show(KeyHandoverAppointment $keyHandoverAppointment): View
    {
        Gate::authorize('view', $keyHandoverAppointment);

        $keyHandoverAppointment->load(['winnerRegistration', 'candidate', 'housingUnit']);

        return view('backoffice.key-handovers.show', compact('keyHandoverAppointment'));
    }

    public function update(UpdateKeyHandoverAppointmentRequest $request, KeyHandoverAppointment $keyHandoverAppointment): RedirectResponse
    {
        Gate::authorize('update', $keyHandoverAppointment);

        $this->appointments->update($keyHandoverAppointment, $request->validated(), $this->authenticatedUser($request));

        return back()->with('success', 'Entrega de chaves reagendada.');
    }

    public function complete(CompleteKeyHandoverAppointmentRequest $request, KeyHandoverAppointment $keyHandoverAppointment): RedirectResponse
    {
        Gate::authorize('update', $keyHandoverAppointment);

        $this->appointments->complete($keyHandoverAppointment, $this->authenticatedUser($request), $request->validated('internal_notes'));

        return back()->with('success', 'Entrega de chaves concluída.');
    }

    public function cancel(CancelKeyHandoverAppointmentRequest $request, KeyHandoverAppointment $keyHandoverAppointment): RedirectResponse
    {
        Gate::authorize('update', $keyHandoverAppointment);

        $this->appointments->cancel($keyHandoverAppointment, $this->authenticatedUser($request), (string) $request->validated('reason'));

        return back()->with('success', 'Entrega de chaves cancelada.');
    }
}
