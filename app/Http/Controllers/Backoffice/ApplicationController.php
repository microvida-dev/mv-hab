<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Contest;
use App\Models\Program;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApplicationController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Application::class);

        $applications = Application::query()
            ->with(['user', 'contest', 'program'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('contest_id'), fn ($query) => $query->where('contest_id', $request->integer('contest_id')))
            ->when($request->filled('program_id'), fn ($query) => $query->where('program_id', $request->integer('program_id')))
            ->when($request->filled('number'), fn ($query) => $query->where('application_number', 'like', '%'.$request->query('number').'%'))
            ->latest('submitted_at')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $statuses = ApplicationStatus::options();
        $contests = Contest::query()->orderBy('title')->get(['id', 'title']);
        $programs = Program::query()->orderBy('name')->get(['id', 'name']);

        return view('backoffice.applications.index', compact(
            'applications',
            'statuses',
            'contests',
            'programs',
        ));
    }

    public function show(Application $application): View
    {
        Gate::authorize('view', $application);

        $application->load([
            'user',
            'contest',
            'program',
            'adhesionRegistration',
            'household.members',
            'household.incomeRecords.incomeSource',
            'currentHousingSituation',
            'applicationDocuments.documentSubmission.currentVersion',
            'applicationDocuments.documentType',
            'declarations',
            'snapshots',
            'statusHistories.changedBy',
            'latestEligibilityCheck',
        ]);

        $this->auditLogger->record(
            event: AuditEvents::ACCESS,
            auditable: $application,
            module: 'applications',
            action: 'backoffice_view',
            description: 'Candidatura consultada no backoffice.',
        );

        return view('backoffice.applications.show', compact('application'));
    }
}
