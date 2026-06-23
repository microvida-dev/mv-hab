<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContestDeadlineType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContestRequest;
use App\Http\Requests\UpdateContestRequest;
use App\Models\Contest;
use App\Models\Program;
use App\Models\User;
use App\Services\Contests\ContestService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ContestController extends Controller
{
    public function __construct(private readonly ContestService $contestService) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Contest::class);

        $contests = Contest::query()
            ->with('program')
            ->latest()
            ->paginate(15);

        return view('admin.contests.index', compact('contests'));
    }

    public function create(): View
    {
        Gate::authorize('create', Contest::class);

        return view('admin.contests.create', $this->formData());
    }

    public function store(StoreContestRequest $request): RedirectResponse
    {
        $contest = $this->contestService->create($request->validated(), $this->authenticatedUser($request));

        return to_route('admin.contests.show', $contest)
            ->with('success', 'Concurso criado com sucesso.');
    }

    public function show(Contest $contest): View
    {
        Gate::authorize('view', $contest);

        $contest->load(['program.municipality', 'deadlines', 'juryMembers.user']);

        return view('admin.contests.show', compact('contest'));
    }

    public function edit(Contest $contest): View
    {
        Gate::authorize('update', $contest);

        $contest->load(['deadlines', 'juryMembers']);

        return view('admin.contests.edit', [
            ...$this->formData(),
            'contest' => $contest,
        ]);
    }

    public function update(UpdateContestRequest $request, Contest $contest): RedirectResponse
    {
        $this->contestService->update($contest, $request->validated(), $this->authenticatedUser($request));

        return to_route('admin.contests.show', $contest)
            ->with('success', 'Concurso atualizado com sucesso.');
    }

    public function publish(Request $request, Contest $contest): RedirectResponse
    {
        Gate::authorize('publish', $contest);

        $this->contestService->publish($contest, $this->authenticatedUser($request));

        return back()->with('success', 'Concurso publicado no portal público.');
    }

    public function destroy(Contest $contest): RedirectResponse
    {
        Gate::authorize('delete', $contest);

        $this->contestService->delete($contest);

        return to_route('admin.contests.index')
            ->with('success', 'Concurso eliminado com sucesso.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'programs' => Program::query()->orderBy('name')->get(['id', 'name', 'status']),
            'deadlineTypes' => ContestDeadlineType::options(),
            'juryUsers' => User::query()
                ->whereHas('roles', fn ($query) => $query->where('name', 'jury'))
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ];
    }
}
