<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentAccessAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\RejectDocumentSubmissionRequest;
use App\Http\Requests\ValidateDocumentSubmissionRequest;
use App\Models\DocumentSubmission;
use App\Services\DocumentIntelligence\DocumentAiManualAnalysisService;
use App\Services\Documents\DocumentAccessService;
use App\Services\Documents\DocumentReviewService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Collection;
use App\Models\DocumentType;

class DocumentReviewController extends Controller
{
    public function __construct(
        private readonly DocumentReviewService $reviewService,
        private readonly DocumentAccessService $accessService,
        private readonly DocumentAiManualAnalysisService $documentAiManualAnalysis,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyBackoffice', DocumentSubmission::class);

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'status' => (string) $request->query('status', ''),
            'document_type_id' => (string) $request->query('document_type_id', ''),
            'context' => (string) $request->query('context', ''),
            'member_state' => (string) $request->query('member_state', ''),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
        ];

        $query = DocumentSubmission::query()
            ->with([
                'documentType',
                'requiredDocument',
                'user',
                'application.user',
                'adhesionRegistration.user',
                'householdMember',
                'incomeRecord.incomeSource',
                'currentHousingSituation',
                'latestDocumentAiAnalysis.latestScore',
            ]);

        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $like = '%'.$search.'%';

            $query->where(function ($query) use ($like): void {
                $query
                    ->where('original_filename', 'like', $like)
                    ->orWhereHas('user', function ($query) use ($like): void {
                        $query->where('name', 'like', $like)->orWhere('email', 'like', $like);
                    })
                    ->orWhereHas('application.user', function ($query) use ($like): void {
                        $query->where('name', 'like', $like)->orWhere('email', 'like', $like);
                    })
                    ->orWhereHas('adhesionRegistration', function ($query) use ($like): void {
                        $query
                            ->where('full_name', 'like', $like)
                            ->orWhereHas('user', function ($query) use ($like): void {
                                $query->where('name', 'like', $like)->orWhere('email', 'like', $like);
                            });
                    })
                    ->orWhereHas('householdMember', function ($query) use ($like): void {
                        $query
                            ->where('full_name', 'like', $like)
                            ->orWhere('nif', 'like', $like)
                            ->orWhere('document_number', 'like', $like);
                    })
                    ->orWhereHas('documentType', function ($query) use ($like): void {
                        $query->where('name', 'like', $like);
                    })
                    ->orWhereHas('requiredDocument', function ($query) use ($like): void {
                        $query->where('name', 'like', $like);
                    });
            });
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['document_type_id'] !== '') {
            $query->where('document_type_id', $filters['document_type_id']);
        }

        if ($filters['context'] !== '') {
            match ($filters['context']) {
                'application' => $query->whereNotNull('application_id'),
                'registration' => $query->whereNotNull('adhesion_registration_id')->whereNull('application_id'),
                default => null,
            };
        }

        if ($filters['member_state'] === 'associated') {
            $query->whereNotNull('household_member_id');
        }

        if ($filters['member_state'] === 'missing') {
            $query->whereNull('household_member_id');
        }

        if ($filters['date_from'] !== '') {
            $query->whereDate('submitted_at', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate('submitted_at', '<=', $filters['date_to']);
        }

        $submissions = $query
            ->latest('submitted_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $documentGroups = $submissions
            ->getCollection()
            ->groupBy(fn (DocumentSubmission $submission): string => $this->reviewQueueGroupKey($submission))
            ->map(fn (Collection $items): array => $this->reviewQueueGroupPayload($items))
            ->values();

        return view('admin.document-reviews.index', [
            'submissions' => $submissions,
            'documentGroups' => $documentGroups,
            'filters' => $filters,
            'hasActiveFilters' => collect($filters)->filter(fn (string $value): bool => $value !== '')->isNotEmpty(),
            'documentTypes' => DocumentType::query()->orderBy('name')->get(['id', 'name']),
            'statusOptions' => [
                'submitted' => 'Submetido',
                'under_review' => 'Em análise',
                'validated' => 'Validado',
                'rejected' => 'Rejeitado',
            ],
        ]);
    }

    public function show(Request $request, DocumentSubmission $documentSubmission): View
    {
        if (Gate::denies('viewBackoffice', $documentSubmission)) {
            $this->accessService->denied(
                $documentSubmission,
                $this->authenticatedUser($request),
                'view',
            );

            abort(403);
        }

        $documentSubmission->load([
            'documentType',
            'requiredDocument',
            'adhesionRegistration.user',
            'householdMember',
            'incomeRecord.incomeSource',
            'currentHousingSituation',
            'versions.uploadedBy',
            'reviews.reviewedBy',
            'accessLogs.user',
            'latestDocumentAiAnalysis.latestScore',
        ]);
        $this->accessService->record($documentSubmission, DocumentAccessAction::View, $documentSubmission->currentVersion, $this->authenticatedUser($request));

        return view('admin.document-reviews.show', ['submission' => $documentSubmission]);
    }

    public function runDocumentAi(Request $request, DocumentSubmission $documentSubmission): RedirectResponse
    {
        Gate::authorize('reviewBackoffice', $documentSubmission);

        $analysis = $this->documentAiManualAnalysis->execute(
            $documentSubmission,
            $this->authenticatedUser($request),
        );

        return to_route('backoffice.document-ai.assistant.show', $analysis)
            ->with('success', 'Análise IA documental executada. A decisão final continua a exigir revisão técnica.');
    }

    public function underReview(
        ValidateDocumentSubmissionRequest $request,
        DocumentSubmission $documentSubmission,
    ): RedirectResponse {
        $submission = $this->reviewService->markUnderReview(
            $documentSubmission,
            $this->authenticatedUser($request),
            $request->validated('internal_notes'),
        );

        return to_route('admin.document-reviews.show', $submission)
            ->with('success', 'Documento colocado em análise.');
    }

    public function validateDocument(
        ValidateDocumentSubmissionRequest $request,
        DocumentSubmission $documentSubmission,
    ): RedirectResponse {
        $submission = $this->reviewService->validate(
            $documentSubmission,
            $this->authenticatedUser($request),
            $request->validated('internal_notes'),
        );

        return to_route('admin.document-reviews.show', $submission)
            ->with('success', 'Documento validado.');
    }

    public function reject(
        RejectDocumentSubmissionRequest $request,
        DocumentSubmission $documentSubmission,
    ): RedirectResponse {
        $submission = $this->reviewService->reject(
            $documentSubmission,
            $this->authenticatedUser($request),
            $request->validated('rejection_reason'),
            $request->validated('internal_notes'),
        );

        return to_route('admin.document-reviews.show', $submission)
            ->with('success', 'Documento rejeitado.');
    }

    private function reviewQueueGroupKey(DocumentSubmission $submission): string
    {
        $applicationId = $submission->getAttribute('application_id') ?? $submission->application?->id;

        if ($applicationId) {
            return 'application-'.$applicationId;
        }

        $registrationId = $submission->getAttribute('adhesion_registration_id') ?? $submission->adhesionRegistration?->id;

        if ($registrationId) {
            return 'registration-'.$registrationId;
        }

        $userId = $submission->getAttribute('user_id') ?? $submission->user?->id;

        if ($userId) {
            return 'user-'.$userId;
        }

        return 'document-'.$submission->id;
    }

    private function reviewQueueMemberName(DocumentSubmission $submission): string
    {
        $member = $submission->householdMember;

        if (! $member) {
            return 'Sem elemento associado';
        }

        return $member->full_name ?: 'Elemento #'.$member->id.' do agregado';
    }

    private function reviewQueueMemberHint(DocumentSubmission $submission): string
    {
        if ($submission->householdMember) {
            return $submission->householdMember->relationship === 'applicant'
                ? 'Requerente associado ao documento'
                : 'Elemento do agregado associado ao documento';
        }

        return 'Documento sem associação explícita ao agregado';
    }

    private function reviewQueueGroupPayload(Collection $items): array
    {
        /** @var DocumentSubmission $first */
        $first = $items->first();

        $documents = $items
            ->map(fn (DocumentSubmission $submission): array => [
                'submission' => $submission,
                'member_name' => $this->reviewQueueMemberName($submission),
                'member_hint' => $this->reviewQueueMemberHint($submission),
                'requirement_label' => $this->reviewQueueRequirementLabel($submission),
                'context_label' => $this->reviewQueueContextLabel($submission),
                'key_field' => $this->reviewQueueKeyField($submission),
                'status_value' => $this->reviewQueueStatusValue($submission),
                'status_label' => $this->reviewQueueStatusLabel($submission),
            ])
            ->values();

        $statusCounts = $items
            ->groupBy(fn (DocumentSubmission $submission): string => $this->reviewQueueStatusValue($submission))
            ->map(fn (Collection $documents, string $status): array => [
                'value' => $status,
                'label' => $this->reviewQueueStatusLabel($documents->first()),
                'count' => $documents->count(),
            ])
            ->values();

        return [
            'key' => $this->reviewQueueGroupKey($first),
            'candidate_name' => $first->application?->user?->name
                ?? $first->user?->name
                ?? $first->adhesionRegistration?->full_name
                ?? $first->adhesionRegistration?->user?->name
                ?? 'Candidato não indicado',
            'candidate_email' => $first->application?->user?->email
                ?? $first->user?->email
                ?? $first->adhesionRegistration?->user?->email,
            'application_id' => $first->getAttribute('application_id') ?? $first->application?->id,
            'registration_id' => $first->getAttribute('adhesion_registration_id') ?? $first->adhesionRegistration?->id,
            'documents' => $documents,
            'total' => $items->count(),
            'status_counts' => $statusCounts,
            'last_submission_at' => $items
                ->max(fn (DocumentSubmission $submission) => $submission->submitted_at ?? $submission->created_at),
            'is_open' => $items->contains(
                fn (DocumentSubmission $submission): bool => ! in_array($this->reviewQueueStatusValue($submission), ['validated'], true)
            ),
        ];
    }

    private function reviewQueueRequirementLabel(DocumentSubmission $submission): string
    {
        return $submission->requiredDocument?->name
            ?? $submission->documentType?->name
            ?? 'Documento sem requisito associado';
    }

    private function reviewQueueContextLabel(DocumentSubmission $submission): string
    {
        if ($submission->getAttribute('application_id') || $submission->application) {
            return 'Candidatura';
        }

        if ($submission->getAttribute('adhesion_registration_id') || $submission->adhesionRegistration) {
            return 'Registo';
        }

        return 'Sem contexto';
    }

    private function reviewQueueKeyField(DocumentSubmission $submission): string
    {
        $analysis = $submission->latestDocumentAiAnalysis;

        if (! $analysis) {
            return 'Por confirmar';
        }

        $fields = $analysis->getAttribute('extracted_fields')
            ?? $analysis->getAttribute('extracted_data')
            ?? $analysis->getAttribute('fields')
            ?? [];

        if (is_string($fields)) {
            $fields = json_decode($fields, true) ?: [];
        }

        $candidates = [
            'nif' => 'NIF',
            'tax_identification_number' => 'NIF',
            'expiry_date' => 'Validade',
            'valid_until' => 'Validade',
            'fiscal_year' => 'Ano fiscal',
            'tax_year' => 'Ano fiscal',
            'document_number' => 'Documento',
        ];

        foreach ($candidates as $key => $label) {
            $value = data_get($fields, $key);

            if ($value) {
                return $label.': '.$value;
            }
        }

        $score = $analysis->latestScore?->score;

        if ($score !== null) {
            return 'Confiança IA: '.$score.'%';
        }

        return 'Por confirmar';
    }

    private function reviewQueueStatusValue(DocumentSubmission $submission): string
    {
        $status = $submission->status;

        if ($status instanceof \BackedEnum) {
            return (string) $status->value;
        }

        return (string) $status;
    }

    private function reviewQueueStatusLabel(DocumentSubmission $submission): string
    {
        $status = $submission->status;

        if (is_object($status) && method_exists($status, 'label')) {
            return $status->label();
        }

        return str((string) $status)->replace('_', ' ')->headline()->toString();
    }

    public function preview(Request $request, DocumentSubmission $documentSubmission): StreamedResponse
    {
        if (Gate::denies('viewBackoffice', $documentSubmission)) {
            $this->accessService->denied(
                $documentSubmission,
                $this->authenticatedUser($request),
                'preview',
            );

            abort(403);
        }

        return $this->accessService->preview(
            $documentSubmission->load('currentVersion'),
            $this->authenticatedUser($request),
        );
    }

    public function download(Request $request, DocumentSubmission $documentSubmission): StreamedResponse
    {
        if (Gate::denies('downloadBackoffice', $documentSubmission)) {
            $this->accessService->denied(
                $documentSubmission,
                $this->authenticatedUser($request),
                'download',
            );

            abort(403);
        }

        return $this->accessService->download($documentSubmission->load('currentVersion'), $this->authenticatedUser($request));
    }

    private function reviewQueueExtractedField(DocumentSubmission $submission, array $keys): ?string
    {
        $analysis = $submission->latestDocumentAiAnalysis;

        if (! $analysis) {
            return null;
        }

        $fields = $analysis->getAttribute('extracted_fields')
            ?? $analysis->getAttribute('extracted_data')
            ?? $analysis->getAttribute('fields')
            ?? [];

        if (is_string($fields)) {
            $fields = json_decode($fields, true) ?: [];
        }

        if (! is_array($fields)) {
            return null;
        }

        foreach ($keys as $key) {
            $value = data_get($fields, $key);

            if (blank($value)) {
                continue;
            }

            if (is_array($value)) {
                continue;
            }

            return (string) $value;
        }

        return null;
    }
}
