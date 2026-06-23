<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LeaseContractController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Contract::class);

        return view('candidate.contracts.index', [
            'contracts' => Contract::query()
                ->processual()
                ->forCandidate($this->authenticatedUser($request))
                ->with(['housingUnit', 'deposit'])
                ->latest()
                ->paginate(10),
        ]);
    }

    public function show(Contract $leaseContract): View
    {
        Gate::authorize('view', $leaseContract);
        $leaseContract->load(['housingUnit', 'deposit', 'generatedDocuments', 'validations', 'signatures']);

        return view('candidate.contracts.show', compact('leaseContract'));
    }
}
