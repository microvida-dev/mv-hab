<?php

namespace App\Http\Controllers\Backoffice\Access;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Access\AccessJustificationRequest;
use App\Http\Requests\Backoffice\Access\StoreBackofficeUserRequest;
use App\Http\Requests\Backoffice\Access\UpdateBackofficeUserRequest;
use App\Models\AccessChangeEvent;
use App\Models\MunicipalTeam;
use App\Models\Role;
use App\Models\User;
use App\Policies\UserAdministrationPolicy;
use App\Services\Access\UserAdministrationService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserAdministrationController extends Controller
{
    public function index(Request $request, UserAdministrationPolicy $policy): View
    {
        abort_unless($policy->viewAny($this->authenticatedUser($request)), 403);

        $users = User::query()
            ->with('roles', 'municipalTeams')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('role'), fn ($query) => $query->whereHas('roles', fn ($roles) => $roles->where('name', $request->string('role')->toString())))
            ->when($request->filled('team'), fn ($query) => $query->whereHas('municipalTeams', fn ($teams) => $teams->where('municipal_teams.id', $request->integer('team'))))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $search = '%'.$request->string('q')->toString().'%';
                $query->where(fn ($inner) => $inner
                    ->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('backoffice.access.users.index', [
            'users' => $users,
            'roles' => Role::query()->orderBy('label')->get(),
            'teams' => MunicipalTeam::query()->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request, UserAdministrationPolicy $policy): View
    {
        abort_unless($policy->create($this->authenticatedUser($request)), 403);

        return view('backoffice.access.users.create', [
            'roles' => Role::query()->orderBy('label')->get(),
            'teams' => MunicipalTeam::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreBackofficeUserRequest $request, UserAdministrationService $users): RedirectResponse
    {
        try {
            $user = $users->create($this->authenticatedUser($request), $request->validated());
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['access' => $exception->getMessage()]);
        }

        return redirect()->route('backoffice.users.show', $user)->with('status', 'Utilizador criado com auditoria.');
    }

    public function show(Request $request, User $user, UserAdministrationPolicy $policy): View
    {
        abort_unless($policy->view($this->authenticatedUser($request), $user), 403);

        return view('backoffice.access.users.show', [
            'user' => $user->load('roles.permissions', 'municipalTeams.manager'),
            'roles' => Role::query()->orderBy('label')->get(),
            'events' => AccessChangeEvent::query()
                ->with('actor', 'role', 'municipalTeam')
                ->where('target_user_id', $user->id)
                ->latest('occurred_at')
                ->paginate(20),
        ]);
    }

    public function edit(Request $request, User $user, UserAdministrationPolicy $policy): View
    {
        abort_unless($policy->update($this->authenticatedUser($request), $user), 403);

        return view('backoffice.access.users.edit', ['user' => $user->load('roles', 'municipalTeams')]);
    }

    public function update(UpdateBackofficeUserRequest $request, User $user, UserAdministrationService $users): RedirectResponse
    {
        try {
            $users->update($this->authenticatedUser($request), $user, $request->validated());
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['access' => $exception->getMessage()]);
        }

        return redirect()->route('backoffice.users.show', $user)->with('status', 'Utilizador atualizado com auditoria.');
    }

    public function deactivate(AccessJustificationRequest $request, User $user, UserAdministrationService $users): RedirectResponse
    {
        try {
            $users->deactivate($this->authenticatedUser($request), $user, $request->validated('justification'));
        } catch (DomainException $exception) {
            return back()->withErrors(['access' => $exception->getMessage()]);
        }

        return back()->with('status', 'Utilizador desativado.');
    }

    public function reactivate(AccessJustificationRequest $request, User $user, UserAdministrationService $users): RedirectResponse
    {
        try {
            $users->reactivate($this->authenticatedUser($request), $user, $request->validated('justification'));
        } catch (DomainException $exception) {
            return back()->withErrors(['access' => $exception->getMessage()]);
        }

        return back()->with('status', 'Utilizador reativado.');
    }

    public function forceMfa(AccessJustificationRequest $request, User $user, UserAdministrationService $users): RedirectResponse
    {
        $users->forceMfa($this->authenticatedUser($request), $user, $request->validated('justification'));

        return back()->with('status', 'MFA obrigatório imposto ao utilizador.');
    }

    public function resetPassword(AccessJustificationRequest $request, User $user, UserAdministrationService $users): RedirectResponse
    {
        $users->requestPasswordReset($this->authenticatedUser($request), $user, $request->validated('justification'));

        return back()->with('status', 'Pedido seguro de reset enviado.');
    }
}
