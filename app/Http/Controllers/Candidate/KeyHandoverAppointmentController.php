<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\KeyHandoverAppointment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class KeyHandoverAppointmentController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', KeyHandoverAppointment::class);

        return view('candidate.key-handovers.index', [
            'appointments' => KeyHandoverAppointment::query()
                ->where('user_id', $this->authenticatedUser($request)->id)
                ->with('housingUnit')
                ->latest()
                ->paginate(15),
        ]);
    }

    public function show(KeyHandoverAppointment $keyHandoverAppointment): View
    {
        Gate::authorize('view', $keyHandoverAppointment);

        $keyHandoverAppointment->load('housingUnit');

        return view('candidate.key-handovers.show', compact('keyHandoverAppointment'));
    }
}
