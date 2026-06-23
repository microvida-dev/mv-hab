<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\CloseContestRequest;
use App\Models\Contest;
use App\Models\ContestClosure;
use App\Services\ContestClosure\ContestClosureService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ContestClosureController extends Controller
{
    public function __construct(private readonly ContestClosureService $closures) {}

    public function show(ContestClosure $contestClosure): View
    {
        Gate::authorize('view', $contestClosure);

        $contestClosure->load('contest');

        return view('backoffice.contest-closures.show', compact('contestClosure'));
    }

    public function close(CloseContestRequest $request, Contest $contest): RedirectResponse
    {
        Gate::authorize('create', ContestClosure::class);

        $closure = $this->closures->close($contest, $this->authenticatedUser($request), $request->validated());

        return to_route('backoffice.contest-closures.show', $closure)->with('success', 'Concurso fechado.');
    }
}
