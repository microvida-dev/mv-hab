<?php

namespace App\Http\Controllers\Backoffice\Access;

use App\Http\Controllers\Controller;
use App\Models\AccessChangeEvent;
use App\Policies\AccessAuditPolicy;
use App\Services\Access\AccessMunicipalScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AccessAuditController extends Controller
{
    public function __construct(private readonly AccessMunicipalScopeService $municipalScope) {}

    public function index(Request $request, AccessAuditPolicy $policy): View
    {
        $actor = $this->authenticatedUser($request);

        abort_unless($policy->viewAny($actor), 403);

        return view('backoffice.access.audit.index', [
            'events' => $this->municipalScope->accessEvents(AccessChangeEvent::query(), $actor)
                ->with('actor', 'targetUser', 'role', 'municipalTeam')
                ->when($request->filled('event'), fn ($query) => $query->where('event_code', $request->string('event')->toString()))
                ->latest('occurred_at')
                ->paginate(30)
                ->withQueryString(),
        ]);
    }
}
