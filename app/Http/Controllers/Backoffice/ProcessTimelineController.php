<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Application;
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

    public function show(Application $application): View
    {
        Gate::authorize('view', $application);
        $timeline = $this->builder->forBackoffice($application);

        return view('backoffice.processes.timeline', [
            'application' => $application,
            'timeline' => $timeline,
            'phases' => $this->formatter->groupByPhase($timeline),
        ]);
    }
}
