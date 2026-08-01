<?php

namespace App\Services\Administrative;

use App\Data\Administrative\CorrectionDifferentialItemData;
use App\Data\Administrative\CorrectionDifferentialResultData;
use App\Enums\CorrectionRequestStatus;
use App\Enums\CorrectionResponseKind;
use App\Enums\CorrectionRevalidationItemType;
use App\Enums\DocumentStatus;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\ApplicationReviewBatchItem;
use App\Models\ApplicationReviewPublication;
use App\Models\ApplicationReviewPublicationResult;
use App\Models\Contest;
use App\Models\CorrectionRequest;
use App\Models\CorrectionRequestItem;
use App\Models\CorrectionResponse;
use App\Models\CorrectionSubmissionReceipt;
use App\Models\DocumentSubmission;
use App\Models\DocumentVersion;
use App\Models\Program;
use App\Services\Support\CanonicalJsonHasher;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class CorrectionDifferentialResolver
{
    public function __construct(
        private readonly CanonicalJsonHasher $hasher,
    ) {}

    public function resolve(
        CorrectionRequest $correctionRequest,
    ): CorrectionDifferentialResultData {
        $request = CorrectionRequest::query()
            ->whereKey($correctionRequest->id)
            ->with([
                'publicationResult.publication',
                'publicationResult.batchItem',
                'submissionReceipt',
                'administrativeProcess',
                'application.contest.program',
                'items',
                'responses.documentVersion',
            ])
            ->firstOrFail();

        if (
            $request->isLegacy()
            || $request->status !== CorrectionRequestStatus::Submitted
        ) {
            throw ValidationException::withMessages([
                'correction_request' => 'Apenas um pedido canónico formalmente submetido pode entrar em segunda análise.',
            ]);
        }

        $result = $this->requiredOriginalResult($request);
        $batchItem = $this->requiredBatchItem($result);
        $receipt = $this->requiredReceipt($request);
        $process = $this->requiredProcess($request);
        $application = $this->requiredApplication($request);
        $this->assertAuthoritativeContext(
            $request,
            $result,
            $batchItem,
            $receipt,
            $process,
            $application,
        );

        $receiptPayload = $receipt->snapshot_payload;
        $originalPayload = $batchItem->snapshot_payload;
        $receiptItems = $this->list($receiptPayload['items'] ?? null);
        $originalDocuments = collect(
            $this->list($originalPayload['documents'] ?? null),
        )->keyBy(static fn (array $document): int => (int) ($document['id'] ?? 0));
        $requestItems = $request->items->keyBy('id');
        $responses = $request->responses->keyBy('id');
        $differentialItems = [];
        $blockers = [];
        $affectedDocumentIds = [];

        foreach ($receiptItems as $receiptItem) {
            $itemId = (int) ($receiptItem['item_id'] ?? 0);
            $requestItem = $requestItems->get($itemId);

            if (! $requestItem instanceof CorrectionRequestItem) {
                $blockers[] = 'O recibo referencia um elemento indisponível.';

                continue;
            }

            $responseSnapshot = $this->map(
                $receiptItem['response'] ?? null,
            );
            $responseId = (int) ($responseSnapshot['response_id'] ?? 0);
            $response = $responses->get($responseId);

            if (! $response instanceof CorrectionResponse) {
                $blockers[] = 'O recibo referencia uma resposta indisponível.';

                continue;
            }

            $kind = CorrectionResponseKind::tryFrom(
                (string) ($responseSnapshot['kind'] ?? ''),
            );

            if (! $kind instanceof CorrectionResponseKind) {
                $blockers[] = 'O recibo contém um tipo de resposta inválido.';

                continue;
            }

            if (
                (int) $response->correction_request_id !== (int) $request->id
                || (int) $response->correction_request_item_id !== $itemId
                || $response->response_kind !== $kind
            ) {
                $blockers[] = 'Uma resposta deixou de corresponder ao recibo formal.';

                continue;
            }

            $resolved = $this->resolveSubmittedItem(
                $request,
                $requestItem,
                $response,
                $receiptItem,
                $responseSnapshot,
                $kind,
                $originalDocuments,
            );
            $differentialItems[] = $resolved['item'];
            $blockers = [
                ...$blockers,
                ...$resolved['blockers'],
            ];

            foreach ($resolved['affected_document_ids'] as $documentId) {
                $affectedDocumentIds[$documentId] = true;
            }
        }

        foreach ($originalDocuments as $originalDocument) {
            $documentId = (int) ($originalDocument['id'] ?? 0);

            if (
                $documentId < 1
                || isset($affectedDocumentIds[$documentId])
                || ($originalDocument['status'] ?? null)
                    !== DocumentStatus::Validated->value
            ) {
                continue;
            }

            $source = $this->carryForwardSource($originalDocument);
            $differentialItems[] = new CorrectionDifferentialItemData(
                key: 'document:'.$documentId,
                classification: CorrectionRevalidationItemType::UnchangedValid,
                correctionRequestItemId: null,
                correctionResponseId: null,
                sourceDocumentSubmissionId: $documentId,
                submittedDocumentSubmissionId: null,
                originalDocumentVersionId: $this->nullableInt(
                    $originalDocument['current_version_id'] ?? null,
                ),
                submittedDocumentVersionId: null,
                requiredDocumentId: $this->nullableInt(
                    $originalDocument['required_document_id'] ?? null,
                ),
                requirementInstance: max(
                    1,
                    (int) ($originalDocument['requirement_instance'] ?? 1),
                ),
                targetType: null,
                targetId: null,
                originalChecksum: $this->nullableString(
                    $originalDocument['checksum'] ?? null,
                ),
                submittedChecksum: null,
                responseKind: null,
                stale: false,
                sourceFingerprint: $this->hasher->hash($source),
                sourceSnapshot: $source,
            );
        }

        usort(
            $differentialItems,
            static fn (
                CorrectionDifferentialItemData $left,
                CorrectionDifferentialItemData $right,
            ): int => $left->key <=> $right->key,
        );
        $blockers = array_values(array_unique($blockers));
        sort($blockers, SORT_STRING);
        $sourceFingerprint = $this->hasher->hash([
            'schema_version' => 1,
            'correction_request_id' => (int) $request->id,
            'original_publication_result_id' => (int) $result->id,
            'original_snapshot_hash' => $result->source_snapshot_hash,
            'submission_receipt_id' => (int) $receipt->id,
            'submission_receipt_hash' => $receipt->snapshot_hash,
            'items' => array_map(
                static fn (
                    CorrectionDifferentialItemData $item,
                ): array => $item->fingerprintPayload(),
                $differentialItems,
            ),
        ]);

        return new CorrectionDifferentialResultData(
            request: $request,
            originalPublicationResult: $result,
            submissionReceipt: $receipt,
            process: $process,
            application: $application,
            items: $differentialItems,
            blockers: $blockers,
            sourceFingerprint: $sourceFingerprint,
        );
    }

    /**
     * @param  array<string, mixed>  $receiptItem
     * @param  array<string, mixed>  $responseSnapshot
     * @param  Collection<int, array<string, mixed>>  $originalDocuments
     * @return array{
     *     item: CorrectionDifferentialItemData,
     *     blockers: list<string>,
     *     affected_document_ids: list<int>
     * }
     */
    private function resolveSubmittedItem(
        CorrectionRequest $request,
        CorrectionRequestItem $requestItem,
        CorrectionResponse $response,
        array $receiptItem,
        array $responseSnapshot,
        CorrectionResponseKind $kind,
        Collection $originalDocuments,
    ): array {
        $blockers = [];
        $sourceDocumentId = $requestItem->source_document_submission_id;
        $submittedDocumentId = $this->nullableInt(
            $responseSnapshot['document_submission_id'] ?? null,
        );
        $versionSnapshot = $this->map(
            $responseSnapshot['document_version'] ?? null,
        );
        $submittedVersionId = $this->nullableInt(
            $versionSnapshot['id'] ?? null,
        );
        $submittedChecksum = $this->nullableString(
            $versionSnapshot['checksum'] ?? null,
        );
        $submittedVersion = $submittedVersionId !== null
            ? DocumentVersion::query()->find($submittedVersionId)
            : null;
        $originalDocument = null;

        if ($sourceDocumentId !== null) {
            $candidate = $originalDocuments->get($sourceDocumentId);
            $originalDocument = is_array($candidate) ? $candidate : null;
        }

        if ($originalDocument === null && $submittedDocumentId !== null) {
            $candidate = $originalDocuments->get($submittedDocumentId);
            $originalDocument = is_array($candidate) ? $candidate : null;
        }

        $classification = match ($kind) {
            CorrectionResponseKind::Justification => CorrectionRevalidationItemType::CandidateJustification,
            CorrectionResponseKind::Explanation => CorrectionRevalidationItemType::DependencyAffected,
            CorrectionResponseKind::Document => $this->documentClassification(
                $sourceDocumentId,
                $submittedDocumentId,
                $submittedVersion,
                $originalDocument,
            ),
        };

        if ($kind === CorrectionResponseKind::Document) {
            $blockers = $this->documentBlockers(
                $request,
                $response,
                $submittedDocumentId,
                $submittedVersionId,
                $submittedChecksum,
                $submittedVersion,
                $versionSnapshot,
            );
        } elseif (! hash_equals(
            hash('sha256', (string) ($responseSnapshot['text'] ?? '')),
            hash('sha256', (string) $response->response_text),
        )) {
            $blockers[] = 'Uma resposta textual diverge do recibo formal.';
        }

        $source = [
            'item' => [
                'id' => (int) $requestItem->id,
                'issue_type' => $requestItem->issue_type->value,
                'required_action' => $requestItem->required_action->value,
                'required_document_id' => $requestItem->required_document_id,
                'requirement_instance' => (int) $requestItem->requirement_instance,
                'target_type' => $requestItem->target_type,
                'target_id' => $requestItem->target_id,
                'source_document_submission_id' => $sourceDocumentId,
            ],
            'response' => [
                'id' => (int) $response->id,
                'kind' => $kind->value,
                'text_hash' => $kind === CorrectionResponseKind::Document
                    ? null
                    : hash('sha256', (string) ($responseSnapshot['text'] ?? '')),
                'document_submission_id' => $submittedDocumentId,
                'document_version_id' => $submittedVersionId,
                'replaces_document_version_id' => $submittedVersion
                    ?->replaces_document_version_id,
                'document_checksum' => $submittedChecksum,
            ],
            'original_document' => $originalDocument === null
                ? null
                : $this->carryForwardSource($originalDocument),
        ];
        $sourceFingerprint = $this->hasher->hash($source);
        $stale = $blockers !== [];
        $affectedDocumentIds = array_values(array_unique(array_filter([
            $sourceDocumentId,
            $submittedDocumentId,
        ], static fn (?int $id): bool => $id !== null)));

        return [
            'item' => new CorrectionDifferentialItemData(
                key: 'request-item:'.$requestItem->id,
                classification: $classification,
                correctionRequestItemId: (int) $requestItem->id,
                correctionResponseId: (int) $response->id,
                sourceDocumentSubmissionId: $sourceDocumentId,
                submittedDocumentSubmissionId: $submittedDocumentId,
                originalDocumentVersionId: $this->nullableInt(
                    $originalDocument['current_version_id'] ?? null,
                ),
                submittedDocumentVersionId: $submittedVersionId,
                requiredDocumentId: $requestItem->required_document_id,
                requirementInstance: (int) $requestItem->requirement_instance,
                targetType: $requestItem->target_type,
                targetId: $requestItem->target_id,
                originalChecksum: $this->nullableString(
                    $originalDocument['checksum'] ?? null,
                ),
                submittedChecksum: $submittedChecksum,
                responseKind: $kind,
                stale: $stale,
                sourceFingerprint: $sourceFingerprint,
                sourceSnapshot: $source,
            ),
            'blockers' => $blockers,
            'affected_document_ids' => $affectedDocumentIds,
        ];
    }

    /** @param array<string, mixed>|null $originalDocument */
    private function documentClassification(
        ?int $sourceDocumentId,
        ?int $submittedDocumentId,
        ?DocumentVersion $submittedVersion,
        ?array $originalDocument,
    ): CorrectionRevalidationItemType {
        if ($sourceDocumentId === null && $originalDocument === null) {
            return CorrectionRevalidationItemType::NewDocument;
        }

        if ($submittedVersion?->replaces_document_version_id !== null) {
            return CorrectionRevalidationItemType::ReplacedDocument;
        }

        return $submittedDocumentId === null
            ? CorrectionRevalidationItemType::DependencyAffected
            : CorrectionRevalidationItemType::ChangedDocument;
    }

    /**
     * @param  array<string, mixed>  $versionSnapshot
     * @return list<string>
     */
    private function documentBlockers(
        CorrectionRequest $request,
        CorrectionResponse $response,
        ?int $submissionId,
        ?int $versionId,
        ?string $checksum,
        ?DocumentVersion $version,
        array $versionSnapshot,
    ): array {
        if ($submissionId === null || $versionId === null || $checksum === null) {
            return ['Uma resposta documental do recibo encontra-se incompleta.'];
        }

        $submission = DocumentSubmission::query()
            ->whereKey($submissionId)
            ->first();
        if (
            ! $submission instanceof DocumentSubmission
            || ! $version instanceof DocumentVersion
        ) {
            return ['Uma versão documental do recibo deixou de estar disponível.'];
        }

        $blockers = [];

        if (
            (int) $submission->user_id !== (int) $request->user_id
            || (int) $submission->application_id !== (int) $request->application_id
            || (int) $version->document_submission_id !== $submissionId
            || (int) $response->document_submission_id !== $submissionId
            || (int) $response->document_version_id !== $versionId
            || ! hash_equals($checksum, (string) $version->checksum)
        ) {
            $blockers[] = 'Uma versão documental já não corresponde ao recibo formal.';
        }

        if ((int) $submission->current_version_id !== $versionId) {
            $blockers[] = 'Existe uma versão documental posterior ao recibo formal.';
        }

        if (
            array_key_exists(
                'replaces_document_version_id',
                $versionSnapshot,
            )
            && $this->nullableInt(
                $versionSnapshot['replaces_document_version_id'],
            ) !== $version->replaces_document_version_id
        ) {
            $blockers[] = 'A proveniência da versão documental diverge do recibo formal.';
        }

        return $blockers;
    }

    private function assertAuthoritativeContext(
        CorrectionRequest $request,
        ApplicationReviewPublicationResult $result,
        ApplicationReviewBatchItem $batchItem,
        CorrectionSubmissionReceipt $receipt,
        AdministrativeProcess $process,
        Application $application,
    ): void {
        $publication = $result->getRelation('publication');
        $contest = $application->getRelation('contest');
        $program = $contest instanceof Contest
            ? $contest->getRelation('program')
            : null;

        if (
            ! $publication instanceof ApplicationReviewPublication
            || ! $program instanceof Program
            || (int) $request->application_review_publication_result_id !== (int) $result->id
            || (int) $request->administrative_process_id !== (int) $process->id
            || (int) $request->application_id !== (int) $application->id
            || (int) $request->user_id !== (int) $application->user_id
            || (int) $receipt->correction_request_id !== (int) $request->id
            || (int) $receipt->application_id !== (int) $application->id
            || (int) $receipt->user_id !== (int) $request->user_id
            || (int) $result->application_review_batch_item_id !== (int) $batchItem->id
            || (int) $result->administrative_process_id !== (int) $process->id
            || (int) $result->application_id !== (int) $application->id
            || (int) $result->user_id !== (int) $request->user_id
            || (int) $result->contest_id !== (int) $application->contest_id
            || (int) $result->municipality_id !== (int) $program->municipality_id
        ) {
            throw ValidationException::withMessages([
                'correction_request' => 'O pedido não possui um contexto processual e municipal coerente.',
            ]);
        }

        $integrityIsValid = $request->source_snapshot_hash !== null
            && hash_equals(
                $request->source_snapshot_hash,
                $result->source_snapshot_hash,
            )
            && hash_equals(
                $result->source_snapshot_hash,
                $batchItem->snapshot_hash,
            )
            && hash_equals(
                $batchItem->snapshot_hash,
                $this->hasher->hash($batchItem->snapshot_payload),
            )
            && hash_equals(
                $receipt->snapshot_hash,
                $this->hasher->hash($receipt->snapshot_payload),
            );

        if (! $integrityIsValid) {
            throw ValidationException::withMessages([
                'correction_request' => 'A integridade das fontes imutáveis não pôde ser confirmada.',
            ]);
        }
    }

    private function requiredOriginalResult(
        CorrectionRequest $request,
    ): ApplicationReviewPublicationResult {
        $result = $request->publicationResult;

        if (! $result instanceof ApplicationReviewPublicationResult) {
            throw ValidationException::withMessages([
                'correction_request' => 'O pedido não possui resultado municipal publicado.',
            ]);
        }

        return $result;
    }

    private function requiredBatchItem(
        ApplicationReviewPublicationResult $result,
    ): ApplicationReviewBatchItem {
        $item = $result->batchItem;

        if (! $item instanceof ApplicationReviewBatchItem) {
            throw ValidationException::withMessages([
                'correction_request' => 'O resultado original não possui snapshot de lote.',
            ]);
        }

        return $item;
    }

    private function requiredReceipt(
        CorrectionRequest $request,
    ): CorrectionSubmissionReceipt {
        $receipt = $request->submissionReceipt;

        if (! $receipt instanceof CorrectionSubmissionReceipt) {
            throw ValidationException::withMessages([
                'correction_request' => 'O pedido não possui recibo formal de submissão.',
            ]);
        }

        return $receipt;
    }

    private function requiredProcess(
        CorrectionRequest $request,
    ): AdministrativeProcess {
        $process = $request->getRelation('administrativeProcess');

        if (! $process instanceof AdministrativeProcess) {
            throw ValidationException::withMessages([
                'correction_request' => 'O pedido não possui processo administrativo.',
            ]);
        }

        return $process;
    }

    private function requiredApplication(
        CorrectionRequest $request,
    ): Application {
        $application = $request->getRelation('application');

        if (! $application instanceof Application) {
            throw ValidationException::withMessages([
                'correction_request' => 'O pedido não possui candidatura.',
            ]);
        }

        return $application;
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    private function carryForwardSource(array $document): array
    {
        return [
            'document_submission_id' => (int) ($document['id'] ?? 0),
            'document_type_id' => $this->nullableInt(
                $document['document_type_id'] ?? null,
            ),
            'required_document_id' => $this->nullableInt(
                $document['required_document_id'] ?? null,
            ),
            'requirement_instance' => max(
                1,
                (int) ($document['requirement_instance'] ?? 1),
            ),
            'status' => $document['status'] ?? null,
            'checksum' => $this->nullableString(
                $document['checksum'] ?? null,
            ),
            'current_version_id' => $this->nullableInt(
                $document['current_version_id'] ?? null,
            ),
            'target' => $this->map($document['target'] ?? null),
            'validated_at' => $document['validated_at'] ?? null,
        ];
    }

    /** @return array<string, mixed> */
    private function map(mixed $value): array
    {
        return is_array($value) && ! array_is_list($value)
            ? $value
            : [];
    }

    /** @return list<array<string, mixed>> */
    private function list(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            $value,
            static fn (mixed $item): bool => is_array($item),
        ));
    }

    private function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : $value;
    }
}
