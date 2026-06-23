<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\DrawConvocation;
use App\Services\Convocations\DrawConvocationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DrawConvocationController extends Controller
{
    public function __construct(private readonly DrawConvocationService $convocations) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', DrawConvocation::class);

        return view('candidate.draw-convocations.index', [
            'convocations' => DrawConvocation::query()
                ->where('user_id', $this->authenticatedUser($request)->id)
                ->with('lotteryDraw.contest')
                ->latest()
                ->paginate(15),
        ]);
    }

    public function show(DrawConvocation $drawConvocation): View
    {
        Gate::authorize('view', $drawConvocation);

        $drawConvocation->load('lotteryDraw.contest');

        return view('candidate.draw-convocations.show', compact('drawConvocation'));
    }

    public function markRead(Request $request, DrawConvocation $drawConvocation): RedirectResponse
    {
        Gate::authorize('view', $drawConvocation);

        $this->convocations->markRead($drawConvocation, $this->authenticatedUser($request));

        return back()->with('success', 'Convocatória marcada como lida.');
    }
}
