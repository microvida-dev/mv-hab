<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\DocumentAiExtractionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\FilterDocumentAiExtractionsRequest;
use App\Http\Requests\Backoffice\MarkDocumentAiFieldReviewRequest;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentAiField;
use App\Models\DocumentAiFlag;
use App\Models\DocumentAiProcessingLog;
use App\Services\Audit\AuditLogger;
use App\Services\DocumentIntelligence\DocumentExtractedFieldPresenter;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DocumentAiExtractionController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly DocumentExtractedFieldPresenter $fieldPresenter,
    ) {}

    public function index(FilterDocumentAiExtractionsRequest $request): View
    {
        Gate::authorize('viewAny', DocumentAiAnalysis::class);
        $filters = array_filter(
            $request->validated(),
            static fn (mixed $value): bool => $value !== null && $value !== ''
        );

        $analyses = DocumentAiAnalysis::query()
            ->with([
                'documentSubmission.documentType',
                'documentSubmission.requiredDocument',
                'documentSubmission.user',
                'documentVersion',
            ])
            ->withCount([
                'fields as extracted_fields_count' => fn ($query) => $query->where('metadata->category', 'structured_extraction'),
                'fields as review_fields_count' => fn ($query) => $query->where('requires_review', true),
            ])
            ->when($filters['document_type'] ?? null, fn ($query, string $value) => $query->where('detected_document_type', $value))
            ->when($filters['extraction_status'] ?? null, fn ($query, string $value) => $query->where('extraction_status', $value))
            ->when(array_key_exists('requires_review', $filters), fn ($query) => $query->where('extraction_requires_manual_review', (bool) $filters['requires_review']))
            ->when($filters['field_key'] ?? null, fn ($query, string $value) => $query->whereHas('fields', fn ($fields) => $fields
                ->where('metadata->category', 'structured_extraction')
                ->where('key', $value)))
            ->when($filters['min_confidence'] ?? null, fn ($query, mixed $value) => $query->where('extraction_confidence', '>=', (float) $value))
            ->when($filters['max_confidence'] ?? null, fn ($query, mixed $value) => $query->where('extraction_confidence', '<=', (float) $value))
            ->when($filters['from'] ?? null, fn ($query, string $value) => $query->whereDate('created_at', '>=', $value))
            ->when($filters['to'] ?? null, fn ($query, string $value) => $query->whereDate('created_at', '<=', $value))
            ->whereNotNull('extraction_status')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('backoffice.document-ai.extractions.index', [
            'analyses' => $analyses,
            'filters' => $filters,
        ]);
    }

    public function show(DocumentAiAnalysis $analysis): View
    {
        Gate::authorize('viewExtractedFields', $analysis);

        $analysis->load([
            'documentSubmission.documentType',
            'documentSubmission.requiredDocument',
            'documentSubmission.user',
            'documentVersion',
            'fields' => fn ($query) => $query->where('metadata->category', 'structured_extraction')->orderBy('id'),
            'flags' => fn ($query) => $query->where('details->category', 'structured_extraction')->latest(),
            'processingLogs' => fn ($query) => $query->where('step', 'like', 'field_extraction%')->latest('created_at'),
        ]);

        $canViewSensitive = request()->user()?->can('viewSensitiveExtractedFields', $analysis) ?? false;
        $canViewHealth = request()->user()?->can('viewHealthExtractedFields', $analysis) ?? false;

        $presentedFields = $analysis->fields
            ->map(fn (DocumentAiField $field): array => $this->fieldPresenter->present($field, $canViewSensitive, $canViewHealth))
            ->values();

        $this->auditLogger->record(
            event: AuditEvents::ACCESS,
            auditable: $analysis,
            module: 'documents',
            action: 'document_ai_extraction_viewed',
            description: 'Consulta de extração estruturada documental por IA.',
            metadata: [
                'document_ai_analysis_id' => $analysis->id,
                'document_submission_id' => $analysis->document_submission_id,
                'can_view_sensitive' => $canViewSensitive,
                'can_view_health' => $canViewHealth,
            ],
        );

        return view('backoffice.document-ai.extractions.show', [
            'analysis' => $analysis,
            'presentedFields' => $presentedFields,
            'canViewSensitive' => $canViewSensitive,
            'canViewHealth' => $canViewHealth,
        ]);
    }

    public function markFieldForReview(MarkDocumentAiFieldReviewRequest $request, DocumentAiField $field): RedirectResponse
    {
        Gate::authorize('markForReview', $field);
        $field->loadMissing('analysis');
        $reason = $request->validated('reason') ?: 'Revisão manual solicitada no painel de extração IA.';
        $metadata = is_array($field->metadata) ? $field->metadata : [];
        $metadata['manual_review_requested_at'] = now()->toIso8601String();
        $metadata['manual_review_reason_present'] = true;

        $field->forceFill([
            'requires_review' => true,
            'metadata' => $metadata,
        ])->save();

        $analysis = $field->analysis;

        if ($analysis instanceof DocumentAiAnalysis) {
            $analysis->forceFill([
                'extraction_status' => DocumentAiExtractionStatus::ManualReview,
                'extraction_requires_manual_review' => true,
                'manual_review_at' => $analysis->manual_review_at ?? now(),
            ])->save();

            $flag = new DocumentAiFlag([
                'code' => 'field_manual_review_requested',
                'severity' => 'medium',
                'message' => $reason,
                'details' => [
                    'category' => 'structured_extraction',
                    'field' => $field->key,
                    'source' => 'backoffice_panel',
                ],
                'requires_manual_review' => true,
            ]);
            $flag->forceFill(['document_ai_analysis_id' => $analysis->id]);
            $flag->save();

            $log = new DocumentAiProcessingLog([
                'step' => 'field_manual_review_requested',
                'level' => 'warning',
                'message' => 'Campo extraído marcado para revisão manual.',
                'context' => ['field_key' => $field->key],
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
                action: 'document_ai_field_marked_review',
                description: 'Campo extraído marcado para revisão manual.',
                metadata: [
                    'document_ai_analysis_id' => $analysis->id,
                    'document_submission_id' => $analysis->document_submission_id,
                    'field_key' => $field->key,
                ],
            );
        }

        return redirect()
            ->route('backoffice.document-ai.extractions.show', $field->document_ai_analysis_id)
            ->with('success', 'Campo marcado para revisão manual.');
    }
}
