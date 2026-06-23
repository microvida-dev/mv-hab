<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use App\Models\Program;
use Illuminate\Contracts\View\View;

class PublicPortalController extends Controller
{
    public function __invoke(): View
    {
        $programs = Program::query()
            ->publiclyVisible()
            ->withCount(['contests' => fn ($query) => $query->publiclyVisible()])
            ->latest('published_at')
            ->take(3)
            ->get();

        $contests = Contest::query()
            ->publiclyVisible()
            ->with('program.municipality')
            ->orderBy('closes_at')
            ->take(6)
            ->get();

        return view('public.portal', compact('programs', 'contests'));
    }
}
