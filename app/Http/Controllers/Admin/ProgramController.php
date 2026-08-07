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
use App\Services\Platform\PlatformMunicipalContextService;
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
        private readonly PlatformMunicipalContextService $platformContext,
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
        $municipality = $this->platformContext->requireMunicipality($actor);
        $municipalities = new Collection([$municipality]);
        $regulatoryProfiles = $this->regulatoryProfiles($actor, $municipality->id);

        return view('admin.programs.create', compact('municipalities', 'regulatoryProfiles'));
    }

    public function store(StoreProgramRequest $request): RedirectResponse
    {
        $actor = $this->authenticatedUser($request);
        $municipality = $this->platformContext->requireMunicipality($actor);
        $data = $request->validated();
        $data['municipality_id'] = $municipality->id;
        $program = $this->programService->create($data, $actor);

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

        $program->load(['rules', 'regulatoryProfile']);
        $actor = $this->authenticatedUser($request);
        $municipalities = Municipality::query()->whereKey($program->municipality_id)->get();
        $regulatoryProfiles = $this->regulatoryProfiles($actor, $program->municipality_id);
        $structuralFieldsLocked = ! $this->platformScope->hasGlobalScope($actor);

        return view('admin.programs.edit', compact('program', 'municipalities', 'regulatoryProfiles', 'structuralFieldsLocked'));
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
    private function regulatoryProfiles(User $actor, int $municipalityId): Collection
    {
        return AffordableRentRegulatoryProfile::query()
            ->with('municipality')
            ->where('status', 'active')
            ->where(fn ($profiles) => $profiles
                ->whereNull('municipality_id')
                ->orWhere('municipality_id', $municipalityId))
            ->when(
                ! $this->platformScope->hasGlobalScope($actor) && (int) $actor->municipality_id !== $municipalityId,
                fn ($query) => $query->whereRaw('1 = 0'),
            )
            ->orderBy('legal_regime')
            ->orderBy('municipality_id')
            ->orderBy('name')
            ->get();
    }
}
