<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\HouseholdRelationship;
use App\Enums\ProfessionalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHouseholdMemberRequest;
use App\Http\Requests\UpdateHouseholdMemberRequest;
use App\Models\AdhesionRegistration;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Services\Candidate\HouseholdMemberService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class HouseholdMemberController extends Controller
{
    public function __construct(private readonly HouseholdMemberService $memberService) {}

    public function index(Request $request): View|RedirectResponse
    {
        $household = $this->householdFor($request);

        if ($household === null) {
            return to_route('candidate.household.show');
        }

        Gate::authorize('view', $household);
        $household->load(['members.incomeRecords']);

        return view('candidate.household-members.index', compact('household'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $household = $this->householdFor($request);

        if ($household === null) {
            return to_route('candidate.household.show');
        }

        Gate::authorize('update', $household);

        return view('candidate.household-members.create', [
            'household' => $household,
            'relationships' => HouseholdRelationship::options(),
            'professionalStatuses' => ProfessionalStatus::options(),
        ]);
    }

    public function store(StoreHouseholdMemberRequest $request): RedirectResponse
    {
        $household = $this->householdFor($request);
        abort_if($household === null, 404);

        $this->memberService->create($household, $request->validated(), $this->authenticatedUser($request));

        return to_route('candidate.household-members.index')
            ->with('success', 'Membro adicionado ao agregado.');
    }

    public function edit(Request $request, HouseholdMember $member): View
    {
        Gate::authorize('update', $member);

        return view('candidate.household-members.edit', [
            'household' => $member->household,
            'member' => $member,
            'relationships' => HouseholdRelationship::options(),
            'professionalStatuses' => ProfessionalStatus::options(),
        ]);
    }

    public function update(
        UpdateHouseholdMemberRequest $request,
        HouseholdMember $member,
    ): RedirectResponse {
        $this->memberService->update($member, $request->validated(), $this->authenticatedUser($request));

        return to_route('candidate.household-members.index')
            ->with('success', 'Membro do agregado atualizado.');
    }

    public function destroy(Request $request, HouseholdMember $member): RedirectResponse
    {
        Gate::authorize('delete', $member);
        $this->memberService->delete($member, $this->authenticatedUser($request));

        return to_route('candidate.household-members.index')
            ->with('success', 'Membro removido do agregado.');
    }

    private function householdFor(Request $request): ?Household
    {
        $registration = $this->authenticatedUser($request)->adhesionRegistration()->first();

        if (! $registration instanceof AdhesionRegistration) {
            return null;
        }

        $household = $registration->household()->first();

        return $household instanceof Household ? $household : null;
    }
}
