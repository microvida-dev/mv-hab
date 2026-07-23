<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentAccessAction;
use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\RejectDocumentSubmissionRequest;
use App\Http\Requests\ValidateDocumentSubmissionRequest;
use App\Models\AdhesionRegistration;
use App\Models\Application;
use App\Models\DocumentSubmission;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentIntelligence\DocumentAiManualAnalysisService;
use App\Services\Documents\DocumentAccessService;
use App\Services\Documents\DocumentReviewService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use LogicException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @phpstan-type ReviewQueueDocument array{
 *     submission: DocumentSubmission,
 *     member_name: string,
 *     member_hint: string,
 *     requirement_label: string,
 *     context_label: string,
 *     key_field: string,
 *     status_value: string,
 *     status_label: string
 * }
 * @phpstan-type ReviewQueueStatusCount array{value: string, label: string, count: int}
 * @phpstan-type ReviewQueueGroup array{
 *     key: string,
 *     candidate_name: string,
 *     candidate_email: string|null,
 *     application_id: int|string|null,
 *     registration_id: int|string|null,
 *     documents: Collection<int, ReviewQueueDocument>,
 *     total: int,
 *     status_counts: Collection<int, ReviewQueueStatusCount>,
 *     last_submission_at: CarbonInterface|null,
 *     is_open: bool
 * }
 */
class DocumentReviewController extends Controller
{
    public function __construct(
        private readonly DocumentReviewService $reviewService,
        private readonly DocumentAccessService $accessService,
        private readonly DocumentAiManualAnalysisService $documentAiManualAnalysis,
        private readonly MunicipalRecordScopeService $municipalScope,
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

        $query = $this->municipalScope
            ->documentSubmissions(DocumentSubmission::query(), $this->authenticatedUser($request))
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

    /**
     * @param  Collection<int, DocumentSubmission>  $items
     * @return ReviewQueueGroup
     */
    private function reviewQueueGroupPayload(Collection $items): array
    {
        $first = $items->first();

        if (! $first instanceof DocumentSubmission) {
            throw new LogicException('Um grupo de revisão documental não pode estar vazio.');
        }

        /** @var Collection<int, ReviewQueueDocument> $documents */
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

        /** @var Collection<int, ReviewQueueStatusCount> $statusCounts */
        $statusCounts = $items
            ->groupBy(fn (DocumentSubmission $submission): string => $this->reviewQueueStatusValue($submission))
            ->map(fn (Collection $documents, string $status): array => $this->reviewQueueStatusCount(
                $documents,
                $status,
            ))
            ->values();

        return [
            'key' => $this->reviewQueueGroupKey($first),
            'candidate_name' => $this->reviewQueueCandidateName($first),
            'candidate_email' => $this->reviewQueueCandidateEmail($first),
            'application_id' => $first->getAttribute('application_id') ?? $first->application?->id,
            'registration_id' => $first->getAttribute('adhesion_registration_id') ?? $first->adhesionRegistration?->id,
            'documents' => $documents,
            'total' => $items->count(),
            'status_counts' => $statusCounts,
            'last_submission_at' => $this->reviewQueueLatestSubmissionAt($items),
            'is_open' => $items->contains(
                fn (DocumentSubmission $submission): bool => ! in_array($this->reviewQueueStatusValue($submission), ['validated'], true)
            ),
        ];
    }

    /**
     * @param  Collection<int, DocumentSubmission>  $documents
     * @return ReviewQueueStatusCount
     */
    private function reviewQueueStatusCount(Collection $documents, string $status): array
    {
        $document = $documents->first();

        if (! $document instanceof DocumentSubmission) {
            throw new LogicException('Um estado da fila documental não pode ter um grupo vazio.');
        }

        return [
            'value' => $status,
            'label' => $this->reviewQueueStatusLabel($document),
            'count' => $documents->count(),
        ];
    }

    private function reviewQueueCandidateName(DocumentSubmission $submission): string
    {
        $candidate = $this->reviewQueueApplicationCandidate($submission);

        if ($candidate instanceof User) {
            return $candidate->name;
        }

        $candidate = $submission->getRelation('user');

        if ($candidate instanceof User) {
            return $candidate->name;
        }

        $registration = $submission->getRelation('adhesionRegistration');
        $fullName = $registration instanceof AdhesionRegistration
            ? $registration->getAttribute('full_name')
            : null;

        if (is_string($fullName) && $fullName !== '') {
            return $fullName;
        }

        $candidate = $registration instanceof AdhesionRegistration
            ? $registration->getRelation('user')
            : null;

        return $candidate instanceof User
            ? $candidate->name
            : 'Candidato não indicado';
    }

    private function reviewQueueCandidateEmail(DocumentSubmission $submission): ?string
    {
        $candidate = $this->reviewQueueApplicationCandidate($submission);

        if ($candidate instanceof User) {
            return $candidate->email;
        }

        $candidate = $submission->getRelation('user');

        if ($candidate instanceof User) {
            return $candidate->email;
        }

        $registration = $submission->getRelation('adhesionRegistration');
        $candidate = $registration instanceof AdhesionRegistration
            ? $registration->getRelation('user')
            : null;

        return $candidate instanceof User ? $candidate->email : null;
    }

    private function reviewQueueApplicationCandidate(DocumentSubmission $submission): ?User
    {
        $application = $submission->getRelation('application');
        $candidate = $application instanceof Application
            ? $application->getRelation('user')
            : null;

        return $candidate instanceof User ? $candidate : null;
    }

    /**
     * @param  Collection<int, DocumentSubmission>  $items
     */
    private function reviewQueueLatestSubmissionAt(Collection $items): ?CarbonInterface
    {
        $latest = null;

        foreach ($items as $submission) {
            $date = $submission->submitted_at ?? $submission->created_at;

            if (! $date instanceof CarbonInterface) {
                continue;
            }

            if ($latest === null || $date->greaterThan($latest)) {
                $latest = $date;
            }
        }

        return $latest;
    }

    private function reviewQueueRequirementLabel(DocumentSubmission $submission): string
    {
        $documentType = $submission->getRelation('documentType');
        $name = $documentType instanceof DocumentType
            ? $documentType->getAttribute('name')
            : null;

        return is_string($name) && $name !== ''
            ? $name
            : 'Documento sem requisito associado';
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

        return $status instanceof DocumentStatus ? $status->value : '';
    }

    private function reviewQueueStatusLabel(DocumentSubmission $submission): string
    {
        $status = $submission->status;

        return $status instanceof DocumentStatus ? $status->label() : '';
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
}
