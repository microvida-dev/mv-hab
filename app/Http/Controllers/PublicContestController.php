<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use Illuminate\Contracts\View\View;

class PublicContestController extends Controller
{
    public function index(): View
    {
        $contests = Contest::query()
            ->publiclyVisible()
            ->with('program.municipality')
            ->orderBy('closes_at')
            ->paginate(12);

        return view('public.contests.index', compact('contests'));
    }

    public function show(string $slug): View
    {
        $contest = Contest::query()
            ->publiclyVisible()
            ->where('slug', $slug)
            ->with(['program.municipality', 'deadlines'])
            ->firstOrFail();

        return view('public.contests.show', compact('contest'));
    }
}
