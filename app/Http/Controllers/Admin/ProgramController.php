<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProgramRequest;
use App\Http\Requests\UpdateProgramRequest;
use App\Models\Municipality;
use App\Models\Program;
use App\Services\Programs\ProgramService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProgramController extends Controller
{
    public function __construct(private readonly ProgramService $programService) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Program::class);

        $programs = Program::query()
            ->with('municipality')
            ->withCount('contests')
            ->latest()
            ->paginate(15);

        return view('admin.programs.index', compact('programs'));
    }

    public function create(): View
    {
        Gate::authorize('create', Program::class);

        $municipalities = Municipality::query()->where('active', true)->orderBy('name')->get();

        return view('admin.programs.create', compact('municipalities'));
    }

    public function store(StoreProgramRequest $request): RedirectResponse
    {
        $program = $this->programService->create($request->validated(), $this->authenticatedUser($request));

        return to_route('admin.programs.show', $program)
            ->with('success', 'Programa criado com sucesso.');
    }

    public function show(Program $program): View
    {
        Gate::authorize('view', $program);

        $program->load(['municipality', 'rules', 'contests']);

        return view('admin.programs.show', compact('program'));
    }

    public function edit(Program $program): View
    {
        Gate::authorize('update', $program);

        $program->load('rules');
        $municipalities = Municipality::query()->where('active', true)->orderBy('name')->get();

        return view('admin.programs.edit', compact('program', 'municipalities'));
    }

    public function update(UpdateProgramRequest $request, Program $program): RedirectResponse
    {
        $this->programService->update($program, $request->validated(), $this->authenticatedUser($request));

        return to_route('admin.programs.show', $program)
            ->with('success', 'Programa atualizado com sucesso.');
    }

    public function publish(Request $request, Program $program): RedirectResponse
    {
        Gate::authorize('publish', $program);

        $this->programService->publish($program, $this->authenticatedUser($request));

        return back()->with('success', 'Programa publicado no portal público.');
    }

    public function destroy(Program $program): RedirectResponse
    {
        Gate::authorize('delete', $program);

        $this->programService->delete($program);

        return to_route('admin.programs.index')
            ->with('success', 'Programa eliminado com sucesso.');
    }
}
