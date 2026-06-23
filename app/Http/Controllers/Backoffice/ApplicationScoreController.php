<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateManualScoreRequest;
use App\Models\ApplicationScore;
use App\Services\Scoring\ApplicationScoreService;
use App\Services\Scoring\ManualScoreService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApplicationScoreController extends Controller
{
    public function __construct(
        private readonly ManualScoreService $manualScoreService,
        private readonly ApplicationScoreService $scoreService,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAny', ApplicationScore::class);
        $scores = ApplicationScore::query()
            ->with(['application.user', 'contest', 'scoringRun'])
            ->latest()
            ->paginate(20);

        return view('backoffice.scoring.application-scores.index', compact('scores'));
    }

    public function show(ApplicationScore $applicationScore): View
    {
        Gate::authorize('view', $applicationScore);
        $applicationScore->load([
            'application.user',
            'application.administrativeProcess',
            'application.latestEligibilityCheck',
            'program',
            'contest',
            'scoringRun',
            'details.criterion',
            'rankingEntry.rankingSnapshot',
        ]);

        return view('backoffice.scoring.application-scores.show', ['score' => $applicationScore]);
    }

    public function manualReview(ApplicationScore $applicationScore): View
    {
        Gate::authorize('manualReview', $applicationScore);
        $applicationScore->load(['application.user', 'details.criterion']);

        return view('backoffice.scoring.application-scores.manual-review', [
            'score' => $applicationScore,
            'pendingDetails' => $this->manualScoreService->pending($applicationScore),
        ]);
    }

    public function updateManualScore(UpdateManualScoreRequest $request, ApplicationScore $applicationScore): RedirectResponse
    {
        $data = $request->validated();
        $updated = $this->manualScoreService->updateManualScore(
            $applicationScore,
            (int) $data['application_score_detail_id'],
            (float) $data['manual_points'],
            $data['manual_notes'] ?? null,
            $this->authenticatedUser($request),
        );

        return to_route('backoffice.scoring.application-scores.show', $updated)
            ->with('success', 'Pontuação manual atualizada.');
    }

    public function lock(Request $request, ApplicationScore $applicationScore): RedirectResponse
    {
        Gate::authorize('lock', $applicationScore);
        $this->scoreService->lock($applicationScore, $this->authenticatedUser($request));

        return back()->with('success', 'Pontuação bloqueada.');
    }
}
