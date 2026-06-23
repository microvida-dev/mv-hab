<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\AdministrativeProcess;
use App\Services\ProcessTracking\ProcessHistoryFormatter;
use App\Services\ProcessTracking\ProcessTimelineBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class ProcessTimelineController extends Controller
{
    public function __construct(
        private readonly ProcessTimelineBuilder $builder,
        private readonly ProcessHistoryFormatter $formatter,
    ) {}

    public function show(AdministrativeProcess $administrativeProcess): View
    {
        Gate::authorize('view', $administrativeProcess);
        $timeline = $this->builder->forCandidate($administrativeProcess);

        return view('candidate.processes.timeline', [
            'process' => $administrativeProcess,
            'timeline' => $timeline,
            'phases' => $this->formatter->groupByPhase($timeline),
        ]);
    }
}
