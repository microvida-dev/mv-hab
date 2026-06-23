<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Contracts\View\View;

class PublicProgramController extends Controller
{
    public function index(): View
    {
        $programs = Program::query()
            ->publiclyVisible()
            ->with('municipality')
            ->withCount(['contests' => fn ($query) => $query->publiclyVisible()])
            ->latest('published_at')
            ->paginate(12);

        return view('public.programs.index', compact('programs'));
    }

    public function show(string $slug): View
    {
        $program = Program::query()
            ->publiclyVisible()
            ->where('slug', $slug)
            ->with([
                'municipality',
                'rules',
                'contests' => fn ($query) => $query->publiclyVisible()->orderBy('closes_at'),
            ])
            ->firstOrFail();

        return view('public.programs.show', compact('program'));
    }
}
