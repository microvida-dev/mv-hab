<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\RankingSnapshot;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Scoring\RankingSnapshotService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RankingSnapshotController extends Controller
{
    public function __construct(
        private readonly RankingSnapshotService $snapshotService,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyBackoffice', RankingSnapshot::class);
        $snapshots = $this->municipalScope
            ->rankingSnapshots(RankingSnapshot::query(), $this->authenticatedUser($request))
            ->with(['scoringRun', 'program', 'contest', 'generatedBy'])
            ->withCount('entries')
            ->latest('snapshot_number')
            ->paginate(20);

        return view('backoffice.scoring.ranking-snapshots.index', compact('snapshots'));
    }

    public function show(RankingSnapshot $rankingSnapshot): View
    {
        Gate::authorize('viewBackoffice', $rankingSnapshot);
        $rankingSnapshot->load([
            'scoringRun.ruleSet',
            'program',
            'contest',
            'generatedBy',
            'entries.application.user',
            'entries.applicationScore',
        ]);

        return view('backoffice.scoring.ranking-snapshots.show', ['snapshot' => $rankingSnapshot]);
    }

    public function lock(Request $request, RankingSnapshot $rankingSnapshot): RedirectResponse
    {
        Gate::authorize('lockBackoffice', $rankingSnapshot);
        $this->snapshotService->lock($rankingSnapshot, $this->authenticatedUser($request));

        return back()->with('success', 'Snapshot bloqueado.');
    }

    public function archive(Request $request, RankingSnapshot $rankingSnapshot): RedirectResponse
    {
        Gate::authorize('archiveBackoffice', $rankingSnapshot);
        $this->snapshotService->archive($rankingSnapshot, $this->authenticatedUser($request));

        return back()->with('success', 'Snapshot arquivado.');
    }
}
