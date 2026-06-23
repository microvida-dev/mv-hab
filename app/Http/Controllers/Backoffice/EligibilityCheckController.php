<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\ApplicationStatus;
use App\Enums\EligibilityCheckType;
use App\Enums\EligibilityResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\RunApplicationEligibilityCheckRequest;
use App\Models\Application;
use App\Models\EligibilityCheck;
use App\Services\Audit\AuditLogger;
use App\Services\Eligibility\EligibilityCheckService;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class EligibilityCheckController extends Controller
{
    public function __construct(
        private readonly EligibilityCheckService $checkService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', EligibilityCheck::class);
        $checks = EligibilityCheck::query()
            ->with(['program', 'contest', 'user', 'application'])
            ->when($request->filled('result'), fn ($query) => $query->where('result', $request->query('result')))
            ->when($request->filled('check_type'), fn ($query) => $query->where('check_type', $request->query('check_type')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('backoffice.eligibility.checks.index', [
            'checks' => $checks,
            'results' => EligibilityResult::options(),
            'types' => EligibilityCheckType::options(),
        ]);
    }

    public function show(EligibilityCheck $eligibilityCheck): View
    {
        Gate::authorize('view', $eligibilityCheck);
        $eligibilityCheck->load([
            'ruleSet',
            'program',
            'contest',
            'user',
            'application',
            'adhesionRegistration',
            'executedBy',
            'results',
            'snapshots',
        ]);
        $this->auditLogger->record(
            event: AuditEvents::ACCESS,
            auditable: $eligibilityCheck,
            module: 'eligibility',
            action: 'check_view',
            description: 'Resultado técnico de elegibilidade consultado.',
        );

        return view('backoffice.eligibility.checks.show', ['check' => $eligibilityCheck]);
    }

    public function rerun(Request $request, EligibilityCheck $eligibilityCheck): RedirectResponse
    {
        Gate::authorize('rerun', $eligibilityCheck);
        $newCheck = $this->checkService->rerun($eligibilityCheck, $this->authenticatedUser($request));

        return to_route('backoffice.eligibility.checks.show', $newCheck)
            ->with('success', 'Verificação reexecutada com os dados atuais.');
    }

    public function runApplication(
        RunApplicationEligibilityCheckRequest $request,
        Application $application,
    ): RedirectResponse {
        if ($application->status === ApplicationStatus::Draft) {
            throw ValidationException::withMessages([
                'application' => 'A verificação formal só pode ser executada após a submissão da candidatura.',
            ]);
        }

        $check = $this->checkService->formalApplicationCheck($application, $this->authenticatedUser($request));

        return to_route('backoffice.eligibility.checks.show', $check)
            ->with('success', 'Verificação formal concluída sem alterar o estado da candidatura.');
    }
}
