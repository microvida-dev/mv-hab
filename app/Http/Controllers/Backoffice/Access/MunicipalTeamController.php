<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice\Access;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Access\MunicipalTeamMemberRequest;
use App\Http\Requests\Backoffice\Access\StoreMunicipalTeamRequest;
use App\Http\Requests\Backoffice\Access\UpdateMunicipalTeamRequest;
use App\Models\AccessChangeEvent;
use App\Models\MunicipalTeam;
use App\Models\User;
use App\Policies\TeamManagementPolicy;
use App\Services\Access\AccessMunicipalScopeService;
use App\Services\Access\MunicipalTeamService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MunicipalTeamController extends Controller
{
    public function __construct(private readonly AccessMunicipalScopeService $municipalScope) {}

    public function index(Request $request, TeamManagementPolicy $policy): View
    {
        $actor = $this->authenticatedUser($request);

        abort_unless($policy->viewAny($actor), 403);

        return view('backoffice.access.teams.index', [
            'teams' => $this->municipalScope->teams(MunicipalTeam::query(), $actor)
                ->with('manager')
                ->withCount('members')
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function create(Request $request, TeamManagementPolicy $policy): View
    {
        $actor = $this->authenticatedUser($request);

        abort_unless($policy->create($actor), 403);

        return view('backoffice.access.teams.create', [
            'users' => $this->municipalScope->users(User::query(), $actor)
                ->where('status', 'active')
                ->orderBy('name')
                ->limit(200)
                ->get(),
        ]);
    }

    public function store(StoreMunicipalTeamRequest $request, MunicipalTeamService $teams): RedirectResponse
    {
        try {
            $team = $teams->create($this->authenticatedUser($request), $request->validated());
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['access' => $exception->getMessage()]);
        }

        return redirect()->route('backoffice.teams.show', $team)->with('status', 'Equipa criada com auditoria.');
    }

    public function show(Request $request, MunicipalTeam $municipalTeam, TeamManagementPolicy $policy): View
    {
        $actor = $this->authenticatedUser($request);

        abort_unless($policy->view($actor, $municipalTeam), 403);

        return view('backoffice.access.teams.show', [
            'team' => $municipalTeam->load('manager', 'members.roles'),
            'users' => $this->municipalScope->users(User::query(), $actor)
                ->where('status', 'active')
                ->orderBy('name')
                ->limit(200)
                ->get(),
            'events' => $this->municipalScope->accessEvents(AccessChangeEvent::query(), $actor)
                ->with('actor', 'targetUser')
                ->where('municipal_team_id', $municipalTeam->id)
                ->latest('occurred_at')
                ->paginate(20),
        ]);
    }

    public function edit(Request $request, MunicipalTeam $municipalTeam, TeamManagementPolicy $policy): View
    {
        $actor = $this->authenticatedUser($request);

        abort_unless($policy->update($actor, $municipalTeam), 403);

        return view('backoffice.access.teams.edit', [
            'team' => $municipalTeam,
            'users' => $this->municipalScope->users(User::query(), $actor)
                ->where('status', 'active')
                ->orderBy('name')
                ->limit(200)
                ->get(),
        ]);
    }

    public function update(UpdateMunicipalTeamRequest $request, MunicipalTeam $municipalTeam, MunicipalTeamService $teams): RedirectResponse
    {
        try {
            $teams->update($this->authenticatedUser($request), $municipalTeam, $request->validated());
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['access' => $exception->getMessage()]);
        }

        return redirect()->route('backoffice.teams.show', $municipalTeam)->with('status', 'Equipa atualizada com auditoria.');
    }

    public function addMember(MunicipalTeamMemberRequest $request, MunicipalTeam $municipalTeam, MunicipalTeamService $teams): RedirectResponse
    {
        $actor = $this->authenticatedUser($request);
        $member = $this->municipalScope->requireUser(
            $actor,
            (int) $request->validated('user_id'),
        );

        try {
            $teams->addMember(
                $actor,
                $municipalTeam,
                $member,
                $request->validated('justification'),
                $request->validated('role_in_team'),
            );
        } catch (DomainException $exception) {
            return back()->withErrors(['access' => $exception->getMessage()]);
        }

        return back()->with('status', 'Membro associado à equipa.');
    }

    public function removeMember(MunicipalTeamMemberRequest $request, MunicipalTeam $municipalTeam, MunicipalTeamService $teams): RedirectResponse
    {
        $actor = $this->authenticatedUser($request);
        $member = $this->municipalScope->requireUser(
            $actor,
            (int) $request->validated('user_id'),
        );

        try {
            $teams->removeMember(
                $actor,
                $municipalTeam,
                $member,
                $request->validated('justification'),
            );
        } catch (DomainException $exception) {
            return back()->withErrors(['access' => $exception->getMessage()]);
        }

        return back()->with('status', 'Membro removido da equipa.');
    }
}
