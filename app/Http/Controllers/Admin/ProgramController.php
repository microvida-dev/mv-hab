<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProgramRequest;
use App\Http\Requests\UpdateProgramRequest;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\Municipality;
use App\Models\Program;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Platform\PlatformOperatorScopeService;
use App\Services\Programs\ProgramService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProgramController extends Controller
{
    public function __construct(
        private readonly ProgramService $programService,
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly PlatformOperatorScopeService $platformScope,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyBackoffice', Program::class);

        $programs = $this->municipalScope
            ->programs(Program::query(), $this->authenticatedUser($request))
            ->with('municipality')
            ->withCount('contests')
            ->latest()
            ->paginate(15);

        return view('admin.programs.index', compact('programs'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('createBackoffice', Program::class);

        $actor = $this->authenticatedUser($request);
        $municipalities = $this->municipalities($actor);
        $regulatoryProfiles = $this->regulatoryProfiles($actor);

        return view('admin.programs.create', compact('municipalities', 'regulatoryProfiles'));
    }

    public function store(StoreProgramRequest $request): RedirectResponse
    {
        $program = $this->programService->create($request->validated(), $this->authenticatedUser($request));

        return to_route('admin.programs.show', $program)
            ->with('success', 'Programa criado com sucesso.');
    }

    public function show(Program $program): View
    {
        Gate::authorize('viewBackoffice', $program);

        $program->load(['municipality', 'regulatoryProfile', 'regulatorySnapshot', 'rules', 'contests']);

        return view('admin.programs.show', compact('program'));
    }

    public function edit(Request $request, Program $program): View
    {
        Gate::authorize('updateBackoffice', $program);

        $program->load('rules');
        $actor = $this->authenticatedUser($request);
        $municipalities = $this->municipalities($actor);
        $regulatoryProfiles = $this->regulatoryProfiles($actor);

        return view('admin.programs.edit', compact('program', 'municipalities', 'regulatoryProfiles'));
    }

    public function update(UpdateProgramRequest $request, Program $program): RedirectResponse
    {
        $this->programService->update($program, $request->validated(), $this->authenticatedUser($request));

        return to_route('admin.programs.show', $program)
            ->with('success', 'Programa atualizado com sucesso.');
    }

    public function publish(Request $request, Program $program): RedirectResponse
    {
        Gate::authorize('publishBackoffice', $program);

        $this->programService->publish($program, $this->authenticatedUser($request));

        return back()->with('success', 'Programa publicado no portal público.');
    }

    public function destroy(Program $program): RedirectResponse
    {
        Gate::authorize('deleteBackoffice', $program);

        $this->programService->delete($program);

        return to_route('admin.programs.index')
            ->with('success', 'Programa eliminado com sucesso.');
    }

    /**
     * @return Collection<int, AffordableRentRegulatoryProfile>
     */
    private function regulatoryProfiles(User $actor): Collection
    {
        return AffordableRentRegulatoryProfile::query()
            ->with('municipality')
            ->where('status', 'active')
            ->when(
                ! $this->platformScope->hasGlobalScope($actor),
                fn ($query) => $actor->municipality_id === null
                    ? $query->whereRaw('1 = 0')
                    : $query->where(fn ($profiles) => $profiles
                        ->whereNull('municipality_id')
                        ->orWhere('municipality_id', $actor->municipality_id)),
            )
            ->orderBy('legal_regime')
            ->orderBy('municipality_id')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Municipality>
     */
    private function municipalities(User $actor): Collection
    {
        return Municipality::query()
            ->where('active', true)
            ->when(
                ! $this->platformScope->hasGlobalScope($actor),
                fn ($query) => $actor->municipality_id === null
                    ? $query->whereRaw('1 = 0')
                    : $query->whereKey($actor->municipality_id),
            )
            ->orderBy('name')
            ->get();
    }
}
