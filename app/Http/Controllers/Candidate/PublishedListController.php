<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\DefinitiveList;
use App\Models\ProvisionalList;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PublishedListController extends Controller
{
    public function index(Request $request): View
    {
        $provisionalEntries = $this->authenticatedUser($request)->applications()
            ->with(['provisionalListEntries.provisionalList.contest', 'definitiveListEntries.definitiveList.contest'])
            ->get()
            ->flatMap(fn (Application $application) => $application->provisionalListEntries);
        $definitiveEntries = $this->authenticatedUser($request)->applications()
            ->with('definitiveListEntries.definitiveList.contest')
            ->get()
            ->flatMap(fn (Application $application) => $application->definitiveListEntries);

        return view('candidate.results.index', compact('provisionalEntries', 'definitiveEntries'));
    }

    public function show(ProvisionalList $provisionalList, Request $request): View
    {
        Gate::authorize('view', $provisionalList);
        $entry = $provisionalList->entries()->with(['application', 'complaints.decision'])->where('user_id', $this->authenticatedUser($request)->id)->firstOrFail();
        $definitiveList = DefinitiveList::query()->where('provisional_list_id', $provisionalList->id)->published()->first();

        return view('candidate.results.show', compact('provisionalList', 'entry', 'definitiveList'));
    }
}
