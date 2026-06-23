<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Hearing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class HearingController extends Controller
{
    public function index(Request $request): View
    {
        $hearings = Hearing::query()->with(['application', 'submissions'])->where('user_id', $this->authenticatedUser($request)->id)->where('candidate_visible', true)->latest()->paginate(10);

        return view('candidate.hearings.index', compact('hearings'));
    }

    public function show(Hearing $hearing): View
    {
        Gate::authorize('view', $hearing);
        $hearing->load(['application', 'submissions.documentSubmission']);

        return view('candidate.hearings.show', compact('hearing'));
    }
}
