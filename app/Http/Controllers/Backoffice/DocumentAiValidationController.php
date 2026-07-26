<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\DocumentAiValidationSeverity;
use App\Enums\DocumentAiValidationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\FilterDocumentAiValidationsRequest;
use App\Http\Requests\Backoffice\MarkDocumentAiValidationReviewRequest;
use App\Http\Requests\Backoffice\RerunDocumentAiValidationRequest;
use App\Models\Application;
use App\Models\DocumentAiValidation;
use App\Models\DocumentAiValidationRun;
use App\Services\Audit\AuditLogger;
use App\Services\DocumentIntelligence\DocumentCandidateValidationPipeline;
use App\Services\DocumentIntelligence\DocumentValidationDashboardService;
use App\Services\DocumentIntelligence\DocumentValidationValuePresenter;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DocumentAiValidationController extends Controller
{
    public function __construct(
        private readonly DocumentValidationDashboardService $dashboardService,
        private readonly DocumentValidationValuePresenter $presenter,
        private readonly DocumentCandidateValidationPipeline $pipeline,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(FilterDocumentAiValidationsRequest $request): View
    {
        Gate::authorize('viewAnyBackoffice', DocumentAiValidation::class);
        $actor = $this->authenticatedUser($request);
        $filters = array_filter(
            $request->validated(),
            static fn (mixed $value): bool => $value !== null && $value !== ''
        );

        return view('backoffice.document-ai.validations.index', [
            'runs' => $this->dashboardService->runs($filters, $actor),
            'filters' => $filters,
            'totals' => $this->dashboardService->totals($actor),
        ]);
    }

    public function show(Request $request, Application $application): View
    {
        Gate::authorize('viewBackoffice', $application);
        $actor = $this->authenticatedUser($request);
        $application->loadMissing(['user', 'contest']);
        $run = $this->dashboardService->latestRunFor($application, $actor);
        $validations = collect();
        $canViewSensitive = false;
        $canViewHealth = false;

        if ($run instanceof DocumentAiValidationRun) {
            Gate::authorize('viewBackoffice', $run);

            $validations = $run->validations
                ->sortBy(function (DocumentAiValidation $validation): string {
                    $severity = $validation->getAttribute('severity');

                    return implode('|', [
                        $validation->validation_group->value,
                        $severity instanceof DocumentAiValidationSeverity ? $severity->value : '',
                        $validation->validation_key,
                    ]);
                });

            $firstValidation = $validations->first();
            if ($firstValidation instanceof DocumentAiValidation) {
                $canViewSensitive = request()->user()?->can('viewSensitive', $firstValidation) ?? false;
                $canViewHealth = request()->user()?->can('viewHealth', $firstValidation) ?? false;
            }

            $this->auditLogger->record(
                event: AuditEvents::ACCESS,
                auditable: $run,
                module: 'documents',
                action: 'document_ai_candidate_validation_viewed',
                description: 'Consulta de validação IA contra candidatura.',
                metadata: [
                    'application_id' => $application->id,
                    'can_view_sensitive' => $canViewSensitive,
                    'can_view_health' => $canViewHealth,
                ],
            );
        }

        return view('backoffice.document-ai.validations.show', [
            'application' => $application,
            'run' => $run,
            'summary' => $run instanceof DocumentAiValidationRun ? $this->dashboardService->summary($run) : null,
            'presentedValidations' => $validations
                ->map(fn (DocumentAiValidation $validation): array => $this->presenter->present($validation, $canViewSensitive, $canViewHealth))
                ->values(),
            'canViewSensitive' => $canViewSensitive,
            'canViewHealth' => $canViewHealth,
        ]);
    }

    public function validation(Request $request, DocumentAiValidation $validation): View
    {
        Gate::authorize('viewBackoffice', $validation);
        $validation->loadMissing(['run.application.user', 'analysis.documentSubmission.documentType']);
        $canViewSensitive = request()->user()?->can('viewSensitive', $validation) ?? false;
        $canViewHealth = request()->user()?->can('viewHealth', $validation) ?? false;

        $this->auditLogger->record(
            event: AuditEvents::ACCESS,
            auditable: $validation,
            module: 'documents',
            action: 'document_ai_candidate_validation_detail_viewed',
            description: 'Consulta de detalhe de divergência IA contra candidatura.',
            metadata: [
                'application_id' => $validation->application_id,
                'document_ai_analysis_id' => $validation->document_ai_analysis_id,
                'can_view_sensitive' => $canViewSensitive,
                'can_view_health' => $canViewHealth,
            ],
        );

        return view('backoffice.document-ai.validations.validation', [
            'validation' => $validation,
            'presentedValidation' => $this->presenter->present($validation, $canViewSensitive, $canViewHealth),
            'canViewSensitive' => $canViewSensitive,
            'canViewHealth' => $canViewHealth,
        ]);
    }

    public function markManualReview(MarkDocumentAiValidationReviewRequest $request, DocumentAiValidation $validation): RedirectResponse
    {
        Gate::authorize('reviewBackoffice', $validation);

        $validation->forceFill([
            'status' => DocumentAiValidationStatus::ManualReview,
            'requires_manual_review' => true,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()?->id,
            'review_notes' => $request->validated('review_notes') ?: 'Revisão manual solicitada no painel de validação IA.',
        ]);
        $validation->save();

        $validation->run?->forceFill([
            'status' => DocumentAiValidationStatus::ManualReview,
            'requires_manual_review' => true,
        ])->save();

        $this->auditLogger->record(
            event: AuditEvents::UPDATE,
            auditable: $validation,
            module: 'documents',
            action: 'document_ai_candidate_validation_marked_review',
            description: 'Resultado de validação IA marcado para revisão manual.',
            metadata: [
                'application_id' => $validation->application_id,
                'document_ai_analysis_id' => $validation->document_ai_analysis_id,
                'validation_key' => $validation->validation_key,
            ],
        );

        return redirect()
            ->route('backoffice.document-ai.validations.validation', $validation)
            ->with('success', 'Validação marcada para revisão manual.');
    }

    public function rerun(RerunDocumentAiValidationRequest $request, Application $application): RedirectResponse
    {
        Gate::authorize('analyzeDocumentsBackoffice', $application);

        $this->pipeline->processApplication($application, $request->user());

        return redirect()
            ->route('backoffice.document-ai.validations.show', $application)
            ->with('success', 'Reprocessamento de validação IA concluído.');
    }
}
