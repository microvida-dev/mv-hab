<?php

namespace Database\Seeders\Demo;

use App\Enums\AdministrativeProcessStatus;
use App\Enums\ApplicationReviewResult;
use App\Enums\ApplicationReviewStatus;
use App\Enums\ApplicationReviewType;
use App\Enums\ApplicationStatus;
use App\Enums\CorrectionIssueType;
use App\Enums\CorrectionRequestItemStatus;
use App\Enums\CorrectionRequestStatus;
use App\Enums\CorrectionRequiredAction;
use App\Enums\CorrectionResponseReviewResult;
use App\Enums\CorrectionResponseStatus;
use App\Enums\DocumentStatus;
use App\Enums\OfficialNotificationStatus;
use App\Enums\OfficialNotificationType;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\ApplicationReview;
use App\Models\CorrectionRequest;
use App\Models\CorrectionResponse;
use App\Models\DocumentSubmission;
use App\Models\DocumentVersion;
use App\Models\OfficialNotification;
use App\Models\User;
use App\Services\Administrative\AdministrativeProcessService;
use App\Services\Administrative\ApplicationReviewService;
use App\Services\Administrative\CorrectionRequestService;
use App\Services\Administrative\CorrectionResponseService;
use App\Services\Documents\DocumentReviewService;
use App\Services\Documents\DocumentUploadService;
use App\Services\Notifications\OfficialNotificationService;
use App\Support\Demo\MunicipalApplicationDemoContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use LogicException;
use RuntimeException;

final class MunicipalApplicationDemoReviewCorrectionSeeder extends Seeder
{
    public const INITIAL_REVIEW_SUMMARY =
        'Análise documental inicial — demonstração municipal';

    public const CORRECTION_REVIEW_SUMMARY =
        'Reanálise após aperfeiçoamento — demonstração municipal';

    public const CORRECTION_SUBJECT =
        'Pedido de aperfeiçoamento documental — recibo de vencimento';

    public const ISSUE_NOTIFICATION_SUBJECT =
        'Pedido de aperfeiçoamento disponível';

    public const ACCEPTED_NOTIFICATION_SUBJECT =
        'Aperfeiçoamento documental aceite';

    private const EXPECTED_DOCUMENT_COUNT = 15;

    public function run(): void
    {
        $context = app(MunicipalApplicationDemoContext::class);
        $context->assertSeederAllowed();

        $candidate = $this->candidate();
        $analyst = $this->analyst();
        $application = $this->application($candidate);

        if ($this->isFinalState($application)) {
            $this->assertFinalState($application);

            return;
        }

        $process = $this->ensureProcessAtDocumentReview(
            $application,
            $analyst,
        );
        $target = $this->targetDocument($application);

        $this->reviewInitialDocuments(
            $application,
            $target,
            $analyst,
        );
        $this->ensureInitialApplicationReview(
            $process,
            $application,
            $target,
            $analyst,
        );

        $process = $this->ensureEligibilityReview(
            $process,
            $analyst,
        );

        $request = $this->ensureCorrectionRequest(
            $process,
            $target,
            $analyst,
            $context->referenceDate(),
        );

        $this->ensureNotification(
            application: $application,
            candidate: $candidate,
            analyst: $analyst,
            request: $request,
            subject: self::ISSUE_NOTIFICATION_SUBJECT,
            body: 'Foi emitido um pedido fictício de aperfeiçoamento '
                .'documental. Substitua o recibo indicado dentro do prazo '
                .'apresentado na área do candidato.',
            requiresAcknowledgement: true,
        );

        $target = $this->ensureReplacement(
            $target,
            $candidate,
        );

        $response = $this->ensureCorrectionResponse(
            $request,
            $target,
            $candidate,
        );

        $target = $this->ensureCorrectedDocumentValidated(
            $target,
            $analyst,
        );

        $response = $this->ensureResponseAccepted(
            $response,
            $analyst,
        );

        $this->ensureCorrectionApplicationReview(
            $process,
            $application,
            $target,
            $analyst,
        );

        $process = $this->ensureFinalEligibilityReview(
            $process,
            $analyst,
        );

        $this->ensureNotification(
            application: $application,
            candidate: $candidate,
            analyst: $analyst,
            request: $request,
            subject: self::ACCEPTED_NOTIFICATION_SUBJECT,
            body: 'A resposta fictícia ao pedido de aperfeiçoamento foi '
                .'validada. O processo municipal de demonstração prossegue '
                .'para análise de requisitos.',
            requiresAcknowledgement: false,
        );

        $this->assertFinalState($application);
    }

    private function candidate(): User
    {
        return User::query()
            ->where(
                'email',
                MunicipalApplicationDemoAccessSeeder::CANDIDATE_EMAIL,
            )
            ->whereHas(
                'municipality',
                static fn ($query) => $query->where(
                    'code',
                    MunicipalApplicationDemoAccessSeeder::MUNICIPALITY_CODE,
                ),
            )
            ->sole();
    }

    private function analyst(): User
    {
        return User::query()
            ->where(
                'email',
                MunicipalApplicationDemoAccessSeeder::ANALYST_EMAIL,
            )
            ->whereHas(
                'municipality',
                static fn ($query) => $query->where(
                    'code',
                    MunicipalApplicationDemoAccessSeeder::MUNICIPALITY_CODE,
                ),
            )
            ->sole();
    }

    private function application(User $candidate): Application
    {
        return Application::query()
            ->where('user_id', $candidate->id)
            ->whereHas(
                'contest',
                static fn ($query) => $query->where(
                    'code',
                    MunicipalApplicationDemoCatalogSeeder::CONTEST_CODE,
                ),
            )
            ->sole();
    }

    private function ensureProcessAtDocumentReview(
        Application $application,
        User $analyst,
    ): AdministrativeProcess {
        if ($application->status !== ApplicationStatus::Submitted) {
            throw new LogicException(
                'A revisão demo exige uma candidatura formalmente submetida.',
            );
        }

        $processes = AdministrativeProcess::withTrashed()
            ->where('application_id', $application->id)
            ->get();

        if ($processes->count() > 1) {
            throw new LogicException(
                'Existem múltiplos processos administrativos para a '
                .'candidatura municipal demo.',
            );
        }

        $process = $processes->first();

        if ($process?->trashed()) {
            throw new LogicException(
                'O processo administrativo demo encontra-se eliminado.',
            );
        }

        $service = app(AdministrativeProcessService::class);

        if (! $process instanceof AdministrativeProcess) {
            $process = $service->createForApplication(
                $application,
                $analyst,
            );
        }

        while (true) {
            $status = $this->processStatus($process);

            if ($status === AdministrativeProcessStatus::Received) {
                $process = $service->assign(
                    $process,
                    $analyst,
                    $analyst,
                );

                continue;
            }

            if ($status === AdministrativeProcessStatus::Assigned) {
                $process = $service->startPreliminaryReview(
                    $process,
                    $analyst,
                );

                continue;
            }

            if (
                $status
                === AdministrativeProcessStatus::PreliminaryReview
            ) {
                $process = $service->startDocumentReview(
                    $process,
                    $analyst,
                );

                continue;
            }

            if (
                $status
                === AdministrativeProcessStatus::DocumentReview
                || $this->isCorrectionOrLaterStatus($status)
            ) {
                break;
            }

            throw new LogicException(
                'O processo demo possui um estado incompatível com a '
                .'revisão documental.',
            );
        }

        if (
            (int) $process->application_id !== (int) $application->id
            || (int) $process->user_id !== (int) $application->user_id
            || (int) $process->assigned_to !== (int) $analyst->id
        ) {
            throw new LogicException(
                'O processo administrativo demo possui associações '
                .'incompatíveis.',
            );
        }

        return $process->refresh();
    }

    private function targetDocument(
        Application $application,
    ): DocumentSubmission {
        return DocumentSubmission::query()
            ->where('application_id', $application->id)
            ->where('requirement_instance', 3)
            ->whereHas(
                'documentType',
                static fn ($query) => $query->where(
                    'code',
                    'recibos_vencimento',
                ),
            )
            ->orderBy('income_record_id')
            ->with([
                'documentType',
                'requiredDocument',
                'currentVersion',
                'versions',
                'reviews',
            ])
            ->firstOrFail();
    }

    private function reviewInitialDocuments(
        Application $application,
        DocumentSubmission $target,
        User $analyst,
    ): void {
        $submissions = DocumentSubmission::query()
            ->where('application_id', $application->id)
            ->with([
                'currentVersion',
                'versions',
                'reviews',
            ])
            ->orderBy('id')
            ->get();

        if ($submissions->count() !== self::EXPECTED_DOCUMENT_COUNT) {
            throw new LogicException(
                'A revisão demo exige exatamente 15 documentos.',
            );
        }

        foreach ($submissions as $submission) {
            if ($submission->id === $target->id) {
                $this->ensureInitiallyRejected(
                    $submission,
                    $analyst,
                );

                continue;
            }

            $this->ensureValidated(
                $submission,
                $analyst,
                'Validação técnica inicial — dados fictícios.',
            );
        }
    }

    private function ensureInitiallyRejected(
        DocumentSubmission $submission,
        User $analyst,
    ): void {
        $submission->refresh()->load(['versions', 'reviews']);

        if ($submission->versions->count() >= 2) {
            return;
        }

        $review = app(DocumentReviewService::class);

        if ($submission->status === DocumentStatus::Submitted) {
            $submission = $review->markUnderReview(
                $submission,
                $analyst,
                'Conferência inicial do recibo fictício.',
            );
        }

        if ($submission->status === DocumentStatus::UnderReview) {
            $submission = $review->reject(
                $submission,
                $analyst,
                'Ficheiro fictício parcialmente ilegível na demonstração.',
                'Solicitar substituição através de pedido de aperfeiçoamento.',
            );
        }

        if ($submission->status !== DocumentStatus::Rejected) {
            throw new LogicException(
                'O documento selecionado não ficou rejeitado para '
                .'aperfeiçoamento.',
            );
        }
    }

    private function ensureValidated(
        DocumentSubmission $submission,
        User $analyst,
        string $notes,
    ): DocumentSubmission {
        $review = app(DocumentReviewService::class);
        $submission->refresh();

        if ($submission->status === DocumentStatus::Submitted) {
            $submission = $review->markUnderReview(
                $submission,
                $analyst,
                $notes,
            );
        }

        if ($submission->status === DocumentStatus::UnderReview) {
            $submission = $review->validate(
                $submission,
                $analyst,
                $notes,
            );
        }

        if ($submission->status !== DocumentStatus::Validated) {
            throw new LogicException(
                'O documento demo não atingiu o estado validado.',
            );
        }

        return $submission;
    }

    private function ensureInitialApplicationReview(
        AdministrativeProcess $process,
        Application $application,
        DocumentSubmission $target,
        User $analyst,
    ): ApplicationReview {
        $review = $this->singleReview(
            $process,
            ApplicationReviewType::Documental,
            self::INITIAL_REVIEW_SUMMARY,
        );

        if (! $review instanceof ApplicationReview) {
            $items = DocumentSubmission::query()
                ->where('application_id', $application->id)
                ->with('documentType')
                ->orderBy('id')
                ->get()
                ->map(
                    static fn (
                        DocumentSubmission $submission,
                    ): array => [
                        'code' => 'DOC-'.$submission->id,
                        'name' => (string) (
                            $submission->documentType->name
                            ?? 'Documento'
                        ),
                        'category' => 'documents',
                        'result' => $submission->id === $target->id
                            ? ApplicationReviewResult::RequiresCorrection->value
                            : ApplicationReviewResult::Passed->value,
                        'message' => $submission->id === $target->id
                            ? 'Documento rejeitado e sujeito a substituição.'
                            : 'Documento conforme na revisão inicial.',
                        'technical_message' => 'Cenário exclusivamente fictício.',
                        'requires_correction' => $submission->id === $target->id,
                        'correction_reason' => $submission->id === $target->id
                                ? 'Substituir o recibo de vencimento '
                                    .'parcialmente ilegível.'
                                : null,
                    ],
                )
                ->values()
                ->all();

            $review = app(ApplicationReviewService::class)
                ->create(
                    $process,
                    [
                        'review_type' => ApplicationReviewType::Documental->value,
                        'summary' => self::INITIAL_REVIEW_SUMMARY,
                        'internal_notes' => 'Revisão fictícia sem decisão administrativa.',
                        'items' => $items,
                    ],
                    $analyst,
                );
        }

        if ($review->status === ApplicationReviewStatus::InProgress) {
            $review = app(ApplicationReviewService::class)
                ->complete(
                    $review,
                    [
                        'result' => ApplicationReviewResult::RequiresCorrection->value,
                        'summary' => self::INITIAL_REVIEW_SUMMARY,
                        'internal_notes' => 'Um documento exige substituição no cenário demo.',
                    ],
                    $analyst,
                );
        }

        if (
            $review->status !== ApplicationReviewStatus::Completed
            || $review->result
                !== ApplicationReviewResult::RequiresCorrection
            || $review->items()->count()
                !== self::EXPECTED_DOCUMENT_COUNT
            || $review->items()
                ->where('requires_correction', true)
                ->count() !== 1
        ) {
            throw new LogicException(
                'A análise documental administrativa demo está incompleta.',
            );
        }

        return $review;
    }

    private function ensureEligibilityReview(
        AdministrativeProcess $process,
        User $analyst,
    ): AdministrativeProcess {
        $status = $this->processStatus($process);

        if ($status === AdministrativeProcessStatus::DocumentReview) {
            return app(AdministrativeProcessService::class)
                ->startEligibilityReview(
                    $process,
                    $analyst,
                );
        }

        if ($this->isCorrectionOrLaterStatus($status)) {
            return $process->refresh();
        }

        throw new LogicException(
            'O processo demo não pode avançar para análise de requisitos.',
        );
    }

    private function ensureCorrectionRequest(
        AdministrativeProcess $process,
        DocumentSubmission $target,
        User $analyst,
        CarbonImmutable $referenceDate,
    ): CorrectionRequest {
        $requests = CorrectionRequest::withTrashed()
            ->where('administrative_process_id', $process->id)
            ->where('subject', self::CORRECTION_SUBJECT)
            ->get();

        if ($requests->count() > 1) {
            throw new LogicException(
                'Existem múltiplos pedidos de aperfeiçoamento demo.',
            );
        }

        $request = $requests->first();

        if ($request?->trashed()) {
            throw new LogicException(
                'O pedido de aperfeiçoamento demo encontra-se eliminado.',
            );
        }

        $service = app(CorrectionRequestService::class);

        if (! $request instanceof CorrectionRequest) {
            if (
                $this->processStatus($process)
                !== AdministrativeProcessStatus::EligibilityReview
            ) {
                throw new LogicException(
                    'O pedido demo só pode ser criado durante a análise '
                    .'de requisitos.',
                );
            }

            $request = $service->create(
                $process,
                [
                    'subject' => self::CORRECTION_SUBJECT,
                    'message' => 'Na demonstração, o recibo de vencimento '
                        .'indicado apresenta legibilidade insuficiente.',
                    'legal_basis' => 'Cenário fictício, sem efeitos '
                        .'administrativos ou jurídicos.',
                    'instructions' => 'Substitua o ficheiro indicado por uma '
                        .'nova versão PDF legível.',
                    'response_deadline_at' => $referenceDate
                        ->addDays(10)
                        ->setTime(17, 0),
                    'internal_notes' => 'Pedido demo gerado pelo seeder.',
                    'items' => [[
                        'issue_type' => CorrectionIssueType::RejectedDocument->value,
                        'title' => 'Substituição de recibo de vencimento',
                        'description' => 'O ficheiro fictício encontra-se '
                            .'parcialmente ilegível.',
                        'required_action' => CorrectionRequiredAction::ReplaceDocument->value,
                        'is_required' => true,
                        'document_type_id' => $target->document_type_id,
                        'required_document_id' => $target->required_document_id,
                        'sort_order' => 1,
                    ]],
                ],
                $analyst,
            );
        }

        if ($request->status === CorrectionRequestStatus::Draft) {
            $request = $service->issue(
                $request,
                $analyst,
            );
        }

        $request->loadMissing('items');

        if (
            ! $request->candidate_visible
            || $request->items->count() !== 1
            || (int) $request->application_id
                !== (int) $process->application_id
            || (int) $request->user_id !== (int) $process->user_id
        ) {
            throw new LogicException(
                'O pedido de aperfeiçoamento demo possui associações '
                .'incompatíveis.',
            );
        }

        $request->refresh();
        $request->load('items');

        return $request;
    }

    private function ensureReplacement(
        DocumentSubmission $submission,
        User $candidate,
    ): DocumentSubmission {
        $submission->refresh()->load(['versions', 'currentVersion']);

        if ($submission->versions->count() === 2) {
            return $this->assertReplacement($submission);
        }

        if (
            $submission->versions->count() !== 1
            || $submission->status !== DocumentStatus::Rejected
        ) {
            throw new LogicException(
                'O documento demo não está preparado para substituição.',
            );
        }

        $previousAiState = config('document-ai.enabled');
        $file = $this->temporaryPdf();

        try {
            config()->set('document-ai.enabled', false);

            $submission = app(DocumentUploadService::class)
                ->replace(
                    $submission,
                    $file,
                    [
                        'notes' => 'Versão corrigida e inteiramente '
                            .'fictícia para demonstração.',
                        'reference_period' => $submission
                            ->reference_period
                            ?->format('Y-m'),
                    ],
                    $candidate,
                );
        } finally {
            config()->set(
                'document-ai.enabled',
                $previousAiState,
            );

            $temporaryPath = $file->getPathname();

            if (is_file($temporaryPath)) {
                @unlink($temporaryPath);
            }
        }

        return $this->assertReplacement($submission);
    }

    private function assertReplacement(
        DocumentSubmission $submission,
    ): DocumentSubmission {
        $submission->refresh()->load([
            'versions',
            'currentVersion',
        ]);

        $versions = $submission->versions
            ->sortBy('version_number')
            ->values();

        if ($versions->count() !== 2) {
            throw new LogicException(
                'A substituição documental demo deve gerar duas versões.',
            );
        }

        $first = $versions->get(0);
        $second = $versions->get(1);

        if (
            ! $first instanceof DocumentVersion
            || ! $second instanceof DocumentVersion
        ) {
            throw new LogicException(
                'As versões documentais demo possuem tipos inválidos.',
            );
        }

        $firstVersionNumber = (int) $first->getAttribute(
            'version_number',
        );
        $secondVersionNumber = (int) $second->getAttribute(
            'version_number',
        );
        $firstStatus = (string) $first->getRawOriginal(
            'status_at_upload',
        );
        $secondStatus = (string) $second->getRawOriginal(
            'status_at_upload',
        );
        $currentVersionId = (int) $submission->getAttribute(
            'current_version_id',
        );
        $firstChecksum = (string) $first->getAttribute(
            'checksum',
        );
        $secondChecksum = (string) $second->getAttribute(
            'checksum',
        );

        if (
            $firstVersionNumber !== 1
            || $secondVersionNumber !== 2
            || $firstStatus !== DocumentStatus::Replaced->value
            || $secondStatus !== DocumentStatus::Submitted->value
            || $currentVersionId !== (int) $second->id
            || $firstChecksum === $secondChecksum
        ) {
            throw new LogicException(
                'O histórico da substituição documental demo é inválido.',
            );
        }

        foreach ($versions as $version) {
            if (
                ! Storage::disk($version->storage_disk)
                    ->exists($version->storage_path)
            ) {
                throw new LogicException(
                    'Uma versão documental privada demo não existe.',
                );
            }
        }

        return $submission;
    }

    private function ensureCorrectionResponse(
        CorrectionRequest $request,
        DocumentSubmission $submission,
        User $candidate,
    ): CorrectionResponse {
        $item = $request->items()
            ->where('is_required', true)
            ->sole();

        $responses = CorrectionResponse::withTrashed()
            ->where('correction_request_id', $request->id)
            ->where('correction_request_item_id', $item->id)
            ->where('user_id', $candidate->id)
            ->get();

        if ($responses->count() > 1) {
            throw new LogicException(
                'Existem múltiplas respostas ao aperfeiçoamento demo.',
            );
        }

        $response = $responses->first();

        if ($response?->trashed()) {
            throw new LogicException(
                'A resposta ao aperfeiçoamento demo encontra-se eliminada.',
            );
        }

        if (! $response instanceof CorrectionResponse) {
            $response = app(CorrectionResponseService::class)
                ->submit(
                    $request,
                    [
                        'correction_request_item_id' => $item->id,
                        'response_text' => 'Documento fictício substituído '
                            .'conforme solicitado.',
                        'document_submission_id' => $submission->id,
                    ],
                    $candidate,
                );
        }

        if (
            (int) $response->document_submission_id
                !== (int) $submission->id
            || $response->submitted_at === null
        ) {
            throw new LogicException(
                'A resposta ao aperfeiçoamento demo não está ligada à '
                .'versão documental corrigida.',
            );
        }

        return $response->refresh();
    }

    private function ensureCorrectedDocumentValidated(
        DocumentSubmission $submission,
        User $analyst,
    ): DocumentSubmission {
        $submission->refresh()->load('versions');

        if ($submission->versions->count() !== 2) {
            throw new LogicException(
                'A reanálise exige a segunda versão documental.',
            );
        }

        return $this->ensureValidated(
            $submission,
            $analyst,
            'Reanálise técnica da versão corrigida — dados fictícios.',
        );
    }

    private function ensureResponseAccepted(
        CorrectionResponse $response,
        User $analyst,
    ): CorrectionResponse {
        if ($response->status === CorrectionResponseStatus::Submitted) {
            $response = app(CorrectionResponseService::class)
                ->accept(
                    $response,
                    [
                        'review_notes' => 'Versão corrigida validada no '
                            .'cenário municipal demo.',
                    ],
                    $analyst,
                );
        }

        $responseStatus = (string) $response->getRawOriginal(
            'status',
        );
        $reviewResult = (string) $response->getRawOriginal(
            'review_result',
        );
        $reviewedAt = $response->getAttribute('reviewed_at');
        $reviewedBy = (int) $response->getAttribute(
            'reviewed_by',
        );

        if (
            $responseStatus
                !== CorrectionResponseStatus::Accepted->value
            || $reviewResult
                !== CorrectionResponseReviewResult::Accepted->value
            || $reviewedAt === null
            || $reviewedBy !== (int) $analyst->id
        ) {
            throw new LogicException(
                'A resposta ao aperfeiçoamento demo não foi aceite.',
            );
        }

        return $response->refresh();
    }

    private function ensureCorrectionApplicationReview(
        AdministrativeProcess $process,
        Application $application,
        DocumentSubmission $target,
        User $analyst,
    ): ApplicationReview {
        $review = $this->singleReview(
            $process,
            ApplicationReviewType::CorrectionResponse,
            self::CORRECTION_REVIEW_SUMMARY,
        );

        if (! $review instanceof ApplicationReview) {
            $review = app(ApplicationReviewService::class)
                ->create(
                    $process,
                    [
                        'review_type' => ApplicationReviewType::CorrectionResponse->value,
                        'summary' => self::CORRECTION_REVIEW_SUMMARY,
                        'internal_notes' => 'Reanálise fictícia da resposta.',
                        'items' => [[
                            'code' => 'CORR-DOC-'.$target->id,
                            'name' => 'Recibo de vencimento substituído',
                            'category' => 'documents',
                            'result' => ApplicationReviewResult::Passed->value,
                            'message' => 'Nova versão documental conforme.',
                            'technical_message' => 'Validação exclusivamente demonstrativa.',
                            'requires_correction' => false,
                            'correction_reason' => null,
                        ]],
                    ],
                    $analyst,
                );
        }

        if ($review->status === ApplicationReviewStatus::InProgress) {
            $review = app(ApplicationReviewService::class)
                ->complete(
                    $review,
                    [
                        'result' => ApplicationReviewResult::Passed->value,
                        'summary' => self::CORRECTION_REVIEW_SUMMARY,
                        'internal_notes' => 'Aperfeiçoamento aceite no cenário demo.',
                    ],
                    $analyst,
                );
        }

        if (
            $review->status !== ApplicationReviewStatus::Completed
            || $review->result !== ApplicationReviewResult::Passed
            || $review->items()->count() !== 1
        ) {
            throw new LogicException(
                'A reanálise administrativa demo está incompleta.',
            );
        }

        if ((int) $review->application_id !== (int) $application->id) {
            throw new LogicException(
                'A reanálise demo pertence a outra candidatura.',
            );
        }

        return $review;
    }

    private function ensureFinalEligibilityReview(
        AdministrativeProcess $process,
        User $analyst,
    ): AdministrativeProcess {
        $process->refresh();
        $status = $this->processStatus($process);

        if (
            $status
            === AdministrativeProcessStatus::CorrectionUnderReview
        ) {
            $process = app(AdministrativeProcessService::class)
                ->startEligibilityReview(
                    $process,
                    $analyst,
                );
        }

        if (
            $this->processStatus($process)
            !== AdministrativeProcessStatus::EligibilityReview
        ) {
            throw new LogicException(
                'O processo demo não regressou à análise de requisitos '
                .'após o aperfeiçoamento.',
            );
        }

        return $process->refresh();
    }

    private function ensureNotification(
        Application $application,
        User $candidate,
        User $analyst,
        CorrectionRequest $request,
        string $subject,
        string $body,
        bool $requiresAcknowledgement,
    ): OfficialNotification {
        $notifications = OfficialNotification::withTrashed()
            ->where('user_id', $candidate->id)
            ->where('application_id', $application->id)
            ->where(
                'notifiable_type',
                $request->getMorphClass(),
            )
            ->where('notifiable_id', $request->id)
            ->where('subject', $subject)
            ->get();

        if ($notifications->count() > 1) {
            throw new LogicException(
                'Existem notificações demo duplicadas para o '
                .'aperfeiçoamento.',
            );
        }

        $notification = $notifications->first();

        if ($notification?->trashed()) {
            throw new LogicException(
                'A notificação demo existente encontra-se eliminada.',
            );
        }

        if (! $notification instanceof OfficialNotification) {
            $notification = app(OfficialNotificationService::class)
                ->createInternal(
                    user: $candidate,
                    type: OfficialNotificationType::Other,
                    subject: $subject,
                    body: $body,
                    notifiable: $request,
                    application: $application,
                    actor: $analyst,
                    requiresAcknowledgement: $requiresAcknowledgement,
                    actionUrl: route(
                        'candidate.correction-requests.show',
                        $request,
                        false,
                    ),
                );
        }

        if (
            $notification->status
                === OfficialNotificationStatus::Draft
            || $notification->status
                === OfficialNotificationStatus::Cancelled
            || (bool) $notification->requires_acknowledgement
                !== $requiresAcknowledgement
            || (int) $notification->user_id
                !== (int) $candidate->id
        ) {
            throw new LogicException(
                'A notificação oficial demo possui estado ou destinatário '
                .'incompatível.',
            );
        }

        return $notification;
    }

    private function singleReview(
        AdministrativeProcess $process,
        ApplicationReviewType $type,
        string $summary,
    ): ?ApplicationReview {
        $reviews = ApplicationReview::withTrashed()
            ->where(
                'administrative_process_id',
                $process->id,
            )
            ->where('review_type', $type->value)
            ->where('summary', $summary)
            ->get();

        if ($reviews->count() > 1) {
            throw new LogicException(
                'Existem análises administrativas demo duplicadas.',
            );
        }

        $review = $reviews->first();

        if ($review?->trashed()) {
            throw new LogicException(
                'A análise administrativa demo encontra-se eliminada.',
            );
        }

        return $review;
    }

    private function isFinalState(
        Application $application,
    ): bool {
        $process = AdministrativeProcess::query()
            ->where('application_id', $application->id)
            ->first();

        if (! $process instanceof AdministrativeProcess) {
            return false;
        }

        $request = CorrectionRequest::query()
            ->where('administrative_process_id', $process->id)
            ->where('subject', self::CORRECTION_SUBJECT)
            ->first();

        if (! $request instanceof CorrectionRequest) {
            return false;
        }

        $target = $this->targetDocument($application);

        return $this->processStatus($process)
                === AdministrativeProcessStatus::EligibilityReview
            && $request->status === CorrectionRequestStatus::Accepted
            && $target->status === DocumentStatus::Validated
            && $target->versions()->count() === 2
            && DocumentSubmission::query()
                ->where('application_id', $application->id)
                ->where(
                    'status',
                    '!=',
                    DocumentStatus::Validated->value,
                )
                ->doesntExist()
            && ApplicationReview::query()
                ->where('administrative_process_id', $process->id)
                ->where('status', ApplicationReviewStatus::Completed->value)
                ->count() === 2
            && OfficialNotification::query()
                ->where('application_id', $application->id)
                ->where(
                    'notifiable_type',
                    $request->getMorphClass(),
                )
                ->where('notifiable_id', $request->id)
                ->whereIn('subject', [
                    self::ISSUE_NOTIFICATION_SUBJECT,
                    self::ACCEPTED_NOTIFICATION_SUBJECT,
                ])
                ->count() === 2;
    }

    private function assertFinalState(
        Application $application,
    ): void {
        $application->refresh();

        if ($application->status !== ApplicationStatus::Submitted) {
            throw new LogicException(
                'A candidatura demo deixou de estar formalmente submetida.',
            );
        }

        $process = AdministrativeProcess::query()
            ->where('application_id', $application->id)
            ->with([
                'statusHistories',
                'reviews.items',
                'correctionRequests.items.responses',
            ])
            ->sole();

        if (
            $this->processStatus($process)
                !== AdministrativeProcessStatus::EligibilityReview
            || (int) $process->assigned_to
                !== (int) $this->analyst()->id
            || $process->statusHistories()->count() !== 10
        ) {
            throw new LogicException(
                'O ciclo administrativo demo não terminou no estado esperado.',
            );
        }

        $submissions = DocumentSubmission::query()
            ->where('application_id', $application->id)
            ->with(['versions', 'reviews'])
            ->get();

        if (
            $submissions->count() !== self::EXPECTED_DOCUMENT_COUNT
            || $submissions->contains(
                static fn (
                    DocumentSubmission $submission,
                ): bool => $submission->status
                    !== DocumentStatus::Validated,
            )
        ) {
            throw new LogicException(
                'A documentação demo não está integralmente validada.',
            );
        }

        $target = $this->assertReplacement(
            $this->targetDocument($application),
        );

        if ($target->reviews()->count() !== 4) {
            throw new LogicException(
                'O documento corrigido não preserva as quatro decisões '
                .'esperadas.',
            );
        }

        if (
            $submissions
                ->where('id', '!=', $target->id)
                ->contains(
                    static fn (
                        DocumentSubmission $submission,
                    ): bool => $submission->versions->count() !== 1
                        || $submission->reviews->count() !== 2,
                )
        ) {
            throw new LogicException(
                'Os restantes documentos demo não preservam o histórico '
                .'esperado.',
            );
        }

        $request = CorrectionRequest::query()
            ->where('administrative_process_id', $process->id)
            ->where('subject', self::CORRECTION_SUBJECT)
            ->with(['items.responses'])
            ->sole();

        $item = $request->items->sole();
        $response = $item->responses->sole();

        $requestStatus = (string) $request->getRawOriginal(
            'status',
        );
        $candidateVisible = (bool) $request->getAttribute(
            'candidate_visible',
        );
        $closedAt = $request->getAttribute('closed_at');
        $itemStatus = (string) $item->getRawOriginal(
            'status',
        );
        $responseStatus = (string) $response->getRawOriginal(
            'status',
        );
        $responseReviewResult = (string) $response
            ->getRawOriginal('review_result');
        $responseDocumentSubmissionId = (int) $response
            ->getAttribute('document_submission_id');

        if (
            $requestStatus
                !== CorrectionRequestStatus::Accepted->value
            || ! $candidateVisible
            || $closedAt === null
            || $itemStatus
                !== CorrectionRequestItemStatus::Accepted->value
            || $responseStatus
                !== CorrectionResponseStatus::Accepted->value
            || $responseReviewResult
                !== CorrectionResponseReviewResult::Accepted->value
            || $responseDocumentSubmissionId !== (int) $target->id
        ) {
            throw new LogicException(
                'O aperfeiçoamento documental demo não está aceite.',
            );
        }

        $reviews = ApplicationReview::query()
            ->where('administrative_process_id', $process->id)
            ->where('status', ApplicationReviewStatus::Completed->value)
            ->get();

        if (
            $reviews->count() !== 2
            || $reviews->contains(
                static fn (
                    ApplicationReview $review,
                ): bool => ! in_array(
                    $review->result,
                    [
                        ApplicationReviewResult::RequiresCorrection,
                        ApplicationReviewResult::Passed,
                    ],
                    true,
                ),
            )
        ) {
            throw new LogicException(
                'As análises administrativas demo estão incompletas.',
            );
        }

        $notificationCount = OfficialNotification::query()
            ->where('application_id', $application->id)
            ->where(
                'notifiable_type',
                $request->getMorphClass(),
            )
            ->where('notifiable_id', $request->id)
            ->whereIn('subject', [
                self::ISSUE_NOTIFICATION_SUBJECT,
                self::ACCEPTED_NOTIFICATION_SUBJECT,
            ])
            ->count();

        if ($notificationCount !== 2) {
            throw new LogicException(
                'As notificações do aperfeiçoamento demo estão incompletas.',
            );
        }
    }

    private function processStatus(
        AdministrativeProcess $process,
    ): AdministrativeProcessStatus {
        $status = $process->getAttribute('status');

        if ($status instanceof AdministrativeProcessStatus) {
            return $status;
        }

        $resolved = is_string($status)
            ? AdministrativeProcessStatus::tryFrom($status)
            : null;

        if (! $resolved instanceof AdministrativeProcessStatus) {
            throw new LogicException(
                'O processo administrativo demo não possui estado válido.',
            );
        }

        return $resolved;
    }

    private function isCorrectionOrLaterStatus(
        AdministrativeProcessStatus $status,
    ): bool {
        return in_array(
            $status,
            [
                AdministrativeProcessStatus::EligibilityReview,
                AdministrativeProcessStatus::RequiresCorrection,
                AdministrativeProcessStatus::AwaitingCandidateResponse,
                AdministrativeProcessStatus::CorrectionSubmitted,
                AdministrativeProcessStatus::CorrectionUnderReview,
            ],
            true,
        );
    }

    private function temporaryPdf(): UploadedFile
    {
        $path = tempnam(
            sys_get_temp_dir(),
            'mvhab-demo-correction-',
        );

        if ($path === false) {
            throw new RuntimeException(
                'Não foi possível criar o PDF temporário de correção.',
            );
        }

        $contents = $this->pdfContents();

        if (file_put_contents($path, $contents) === false) {
            @unlink($path);

            throw new RuntimeException(
                'Não foi possível escrever o PDF temporário de correção.',
            );
        }

        return new UploadedFile(
            path: $path,
            originalName: 'demo-correction-document-001.pdf',
            mimeType: 'application/pdf',
            error: null,
            test: true,
        );
    }

    private function pdfContents(): string
    {
        $stream = implode("\n", [
            'BT',
            '/F1 12 Tf',
            '72 720 Td',
            '(MV-HAB Demo Corrected Document) Tj',
            '0 -20 Td',
            '(Fictional data - no administrative effect) Tj',
            'ET',
            '',
        ]);

        $objects = [
            "1 0 obj\n"
                ."<< /Type /Catalog /Pages 2 0 R >>\n"
                ."endobj\n",
            "2 0 obj\n"
                ."<< /Type /Pages /Kids [3 0 R] /Count 1 >>\n"
                ."endobj\n",
            "3 0 obj\n"
                .'<< /Type /Page /Parent 2 0 R '
                .'/MediaBox [0 0 595 842] '
                .'/Resources << /Font << /F1 5 0 R >> >> '
                ."/Contents 4 0 R >>\n"
                ."endobj\n",
            "4 0 obj\n"
                .'<< /Length '.strlen($stream)." >>\n"
                ."stream\n"
                .$stream
                ."endstream\n"
                ."endobj\n",
            "5 0 obj\n"
                .'<< /Type /Font /Subtype /Type1 '
                ."/BaseFont /Helvetica >>\n"
                ."endobj\n",
        ];

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";

        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\n";
        $pdf .= "startxref\n".$xrefOffset."\n%%EOF\n";

        return $pdf;
    }
}
