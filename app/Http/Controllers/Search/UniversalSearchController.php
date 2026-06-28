<?php

namespace App\Http\Controllers\Search;

use App\Http\Controllers\Controller;
use App\Http\Requests\Search\UniversalSearchRequest;
use App\Services\Search\UniversalSearchService;
use Illuminate\Contracts\View\View;

class UniversalSearchController extends Controller
{
    public function index(UniversalSearchRequest $request, UniversalSearchService $search): View
    {
        $user = $this->authenticatedUser($request);
        $term = $request->term();

        return view('search.index', [
            'term' => $term,
            'search' => $search->search($user, $term),
            'commands' => $search->commands($user, $term),
        ]);
    }

    public function commands(UniversalSearchRequest $request, UniversalSearchService $search): View
    {
        $user = $this->authenticatedUser($request);
        $term = $request->term();

        return view('search.commands', [
            'term' => $term,
            'commands' => $search->commands($user, $term),
        ]);
    }
}
