<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\DocumentAiClassificationStatus;
use App\Enums\DocumentAiStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\FilterDocumentAiClassificationsRequest;
use App\Http\Requests\Backoffice\MarkDocumentAiManualReviewRequest;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentAiFlag;
use App\Models\DocumentAiProcessingLog;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DocumentAiClassificationController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(FilterDocumentAiClassificationsRequest $request): View
    {
        Gate::authorize('viewAny', DocumentAiAnalysis::class);
        $filters = array_filter(
            $request->validated(),
            static fn (mixed $value): bool => $value !== null && $value !== ''
        );

        $analyses = DocumentAiAnalysis::query()
            ->with(['documentSubmission.documentType', 'documentSubmission.requiredDocument', 'documentSubmission.user', 'documentVersion'])
            ->when($filters['document_type'] ?? null, fn ($query, string $value) => $query->where('detected_document_type', $value))
            ->when($filters['classification_status'] ?? null, fn ($query, string $value) => $query->where('classification_status', $value))
            ->when(array_key_exists('ocr_available', $filters), fn ($query) => $query->where('ocr_available', (bool) $filters['ocr_available']))
            ->when(array_key_exists('requires_manual_review', $filters), fn ($query) => $query->where('classification_requires_manual_review', (bool) $filters['requires_manual_review']))
            ->when($filters['min_confidence'] ?? null, fn ($query, mixed $value) => $query->where('classification_confidence', '>=', (float) $value))
            ->when($filters['max_confidence'] ?? null, fn ($query, mixed $value) => $query->where('classification_confidence', '<=', (float) $value))
            ->when($filters['from'] ?? null, fn ($query, string $value) => $query->whereDate('created_at', '>=', $value))
            ->when($filters['to'] ?? null, fn ($query, string $value) => $query->whereDate('created_at', '<=', $value))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('backoffice.document-ai.classifications.index', [
            'analyses' => $analyses,
            'filters' => $filters,
        ]);
    }

    public function show(DocumentAiAnalysis $analysis): View
    {
        Gate::authorize('view', $analysis);

        $analysis->load([
            'documentSubmission.documentType',
            'documentSubmission.requiredDocument',
            'documentSubmission.user',
            'documentVersion',
            'fields',
            'flags',
            'processingLogs',
        ]);

        $this->auditLogger->record(
            event: AuditEvents::ACCESS,
            auditable: $analysis,
            module: 'documents',
            action: 'document_ai_classification_viewed',
            description: 'Consulta de classificação documental por IA.',
            metadata: [
                'document_ai_analysis_id' => $analysis->id,
                'document_submission_id' => $analysis->document_submission_id,
            ],
        );

        return view('backoffice.document-ai.classifications.show', [
            'analysis' => $analysis,
            'canViewSensitive' => request()->user()?->can('viewSensitiveOutput', $analysis) ?? false,
        ]);
    }

    public function markManualReview(MarkDocumentAiManualReviewRequest $request, DocumentAiAnalysis $analysis): RedirectResponse
    {
        Gate::authorize('markManualReview', $analysis);
        $reason = $request->validated('reason') ?: 'Revisão manual solicitada no painel de classificação IA.';

        $analysis->forceFill([
            'status' => DocumentAiStatus::ManualReview,
            'classification_status' => DocumentAiClassificationStatus::ManualReview,
            'classification_requires_manual_review' => true,
            'manual_review_at' => now(),
            'failure_reason' => $reason,
        ])->save();

        $flag = new DocumentAiFlag([
            'code' => 'manual_review_requested',
            'severity' => 'medium',
            'message' => $reason,
            'details' => ['source' => 'backoffice_panel'],
            'requires_manual_review' => true,
        ]);
        $flag->forceFill(['document_ai_analysis_id' => $analysis->id]);
        $flag->save();

        $log = new DocumentAiProcessingLog([
            'step' => 'manual_review_requested',
            'level' => 'warning',
            'message' => 'Revisão manual solicitada no painel de classificação IA.',
            'context' => ['document_ai_analysis_id' => $analysis->id],
        ]);
        $log->forceFill([
            'document_ai_analysis_id' => $analysis->id,
            'created_at' => now(),
        ]);
        $log->save();

        $this->auditLogger->record(
            event: AuditEvents::UPDATE,
            auditable: $analysis,
            module: 'documents',
            action: 'document_ai_manual_review_marked',
            description: 'Classificação documental marcada para revisão manual.',
            metadata: [
                'document_ai_analysis_id' => $analysis->id,
                'document_submission_id' => $analysis->document_submission_id,
            ],
        );

        return redirect()
            ->route('backoffice.document-ai.classifications.show', $analysis)
            ->with('success', 'Classificação marcada para revisão manual.');
    }
}
