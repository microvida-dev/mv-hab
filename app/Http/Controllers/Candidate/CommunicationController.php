<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\CommunicationLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommunicationController extends Controller
{
    public function index(Request $request): View
    {
        return view('candidate.communications.index', [
            'communications' => CommunicationLog::query()
                ->where('recipient_user_id', $this->authenticatedUser($request)->id)
                ->with('deliveries')
                ->latest()
                ->paginate(15),
        ]);
    }

    public function show(CommunicationLog $communicationLog): View
    {
        Gate::authorize('view', $communicationLog);
        $communicationLog->load(['deliveries', 'receipts']);

        return view('candidate.communications.show', compact('communicationLog'));
    }
}
