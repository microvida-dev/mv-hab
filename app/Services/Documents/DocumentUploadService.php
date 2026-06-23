<?php

namespace App\Services\Documents;

use App\Enums\AdhesionRegistrationStatus;
use App\Enums\DocumentAccessAction;
use App\Enums\DocumentAppliesTo;
use App\Enums\DocumentStatus;
use App\Models\AdhesionRegistration;
use App\Models\Application;
use App\Models\CurrentHousingSituation;
use App\Models\DocumentSubmission;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\IncomeRecord;
use App\Models\RequiredDocument;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\DocumentIntelligence\DocumentAiPipeline;
use App\Support\AuditEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class DocumentUploadService
{
    public function __construct(
        private readonly DocumentAccessService $accessService,
        private readonly AuditLogger $auditLogger,
        private readonly DocumentAiPipeline $documentAiPipeline,
    ) {}

    /** @param array<string, mixed> $data */
    public function store(
        AdhesionRegistration $registration,
        UploadedFile $file,
        array $data,
        User $actor,
    ): DocumentSubmission {
        $this->ensureEditable($registration);

        $requiredDocument = RequiredDocument::query()
            ->with('documentType')
            ->where('is_active', true)
            ->whereKey($data['required_document_id'] ?? null)
            ->firstOrFail();
        $application = $this->applicationFor($registration, $data['application_public_id'] ?? null);
        $this->ensureRuleScope($requiredDocument, $application);
        $this->ensureTypeMatches($requiredDocument, (int) $data['document_type_id']);
        $this->validateFile($requiredDocument, $file);

        $target = $this->targetFor($registration, $requiredDocument->required_for, $data, $application);
        $this->ensureNoActiveSubmission($registration, $requiredDocument, $target, $application);

        $result = DB::transaction(function () use ($registration, $file, $data, $actor, $requiredDocument, $target, $application) {
            $submission = new DocumentSubmission($this->safeSubmissionData($data));
            $submission->forceFill([
                'document_type_id' => $requiredDocument->document_type_id,
                'required_document_id' => $requiredDocument->id,
                'user_id' => $actor->id,
                'adhesion_registration_id' => $registration->id,
                'status' => DocumentStatus::Submitted,
                'submitted_at' => now(),
                'submitted_by' => $actor->id,
                'application_id' => $application?->id,
                ...$this->targetColumns($requiredDocument->required_for, $target),
            ]);
            $submission->save();

            $version = $this->storeVersion($submission, $file, 1, $actor, $data['notes'] ?? null);
            $this->syncSubmissionFile($submission, $version, $data);

            $this->accessService->record($submission, DocumentAccessAction::Upload, $version, $actor);
            $this->auditLogger->record(
                event: AuditEvents::CREATE,
                auditable: $submission,
                module: 'documents',
                action: 'upload',
                description: 'Documento submetido pelo candidato.',
                metadata: [
                    'actor_id' => $actor->id,
                    'document_type_id' => $requiredDocument->document_type_id,
                    'required_document_id' => $requiredDocument->id,
                    'version' => 1,
                ],
            );
            $this->scheduleDocumentAiAnalysis($submission, $actor);

            return $submission->fresh(['documentType', 'requiredDocument', 'currentVersion', 'versions']);
        });

        assert($result instanceof DocumentSubmission);

        return $result;
    }

    /**
     * @param  array{notes?: string|null, title?: string|null, issue_date?: mixed, expiry_date?: mixed}  $data
     */
    public function replace(DocumentSubmission $submission, UploadedFile $file, array $data, User $actor): DocumentSubmission
    {
        $submission->loadMissing(['documentType', 'requiredDocument.documentType', 'currentVersion', 'adhesionRegistration']);
        $registration = $submission->adhesionRegistration;
        assert($registration instanceof AdhesionRegistration);
        $this->ensureEditable($registration);

        if (! $submission->isReplaceable()) {
            throw ValidationException::withMessages([
                'file' => 'Este documento não pode ser substituído no estado atual.',
            ]);
        }

        $requiredDocument = $submission->requiredDocument;
        abort_if(! $requiredDocument instanceof RequiredDocument, 404);
        $this->validateFile($requiredDocument, $file);

        $result = DB::transaction(function () use ($submission, $file, $data, $actor) {
            $previousStatus = $submission->status;
            $previousVersion = $submission->currentVersion;
            $nextVersion = ((int) $submission->versions()->max('version_number')) + 1;

            if ($previousVersion) {
                $previousVersion->forceFill(['status_at_upload' => DocumentStatus::Replaced])->save();
            }

            $version = $this->storeVersion($submission, $file, $nextVersion, $actor, $data['notes'] ?? null);
            $this->syncSubmissionFile($submission, $version, $data);
            $submission->forceFill([
                'status' => DocumentStatus::Submitted,
                'reviewed_at' => null,
                'reviewed_by' => null,
                'validated_at' => null,
                'validated_by' => null,
                'rejected_at' => null,
                'rejected_by' => null,
                'rejection_reason' => null,
            ])->save();

            $this->accessService->record($submission, DocumentAccessAction::Replace, $version, $actor);
            $this->auditLogger->record(
                event: AuditEvents::UPDATE,
                auditable: $submission,
                module: 'documents',
                action: 'replace',
                description: 'Documento substituído pelo candidato.',
                metadata: [
                    'actor_id' => $actor->id,
                    'from_status' => $previousStatus?->value,
                    'to_status' => DocumentStatus::Submitted->value,
                    'version' => $nextVersion,
                ],
            );
            $this->scheduleDocumentAiAnalysis($submission, $actor);

            return $submission->fresh(['documentType', 'requiredDocument', 'currentVersion', 'versions']);
        });

        assert($result instanceof DocumentSubmission);

        return $result;
    }

    public function cancel(DocumentSubmission $submission, User $actor): void
    {
        $registration = $submission->adhesionRegistration;
        assert($registration instanceof AdhesionRegistration);
        $this->ensureEditable($registration);

        DB::transaction(function () use ($submission, $actor) {
            $submission->forceFill(['status' => DocumentStatus::Cancelled])->save();
            $this->accessService->record($submission, DocumentAccessAction::Delete, $submission->currentVersion, $actor);
            $this->auditLogger->record(
                event: AuditEvents::DELETE,
                auditable: $submission,
                module: 'documents',
                action: 'cancel',
                description: 'Documento cancelado pelo candidato.',
                metadata: ['actor_id' => $actor->id],
            );
        });
    }

    private function storeVersion(
        DocumentSubmission $submission,
        UploadedFile $file,
        int $versionNumber,
        User $actor,
        ?string $notes,
    ): DocumentVersion {
        $extension = Str::lower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $storedFilename = Str::uuid()->toString().'.'.$extension;
        $directory = 'documents/'.$submission->adhesion_registration_id.'/'.$submission->id.'/'.$versionNumber;
        $storagePath = Storage::disk('local')->putFileAs($directory, $file, $storedFilename);
        $checksum = hash_file('sha256', $file->getRealPath());
        $mimeType = $file->getMimeType() ?: $file->getClientMimeType() ?: 'application/octet-stream';

        $version = new DocumentVersion(['notes' => $notes]);
        $version->forceFill([
            'document_submission_id' => $submission->id,
            'version_number' => $versionNumber,
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => $storedFilename,
            'storage_disk' => 'local',
            'storage_path' => $storagePath,
            'mime_type' => $mimeType,
            'file_size' => $file->getSize(),
            'checksum' => $checksum,
            'uploaded_by' => $actor->id,
            'uploaded_at' => now(),
            'status_at_upload' => DocumentStatus::Submitted,
        ]);
        $version->save();

        return $version;
    }

    /**
     * @param  array{issue_date?: mixed, expiry_date?: mixed}  $data
     */
    private function syncSubmissionFile(DocumentSubmission $submission, DocumentVersion $version, array $data): void
    {
        $submission->forceFill([
            'original_filename' => $version->original_filename,
            'stored_filename' => $version->stored_filename,
            'storage_disk' => $version->storage_disk,
            'storage_path' => $version->storage_path,
            'mime_type' => $version->mime_type,
            'file_size' => $version->file_size,
            'checksum' => $version->checksum,
            'current_version_id' => $version->id,
            'issue_date' => $data['issue_date'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
        ]);
        $submission->save();
    }

    private function validateFile(RequiredDocument $requiredDocument, UploadedFile $file): void
    {
        $documentType = $requiredDocument->documentType;
        assert($documentType instanceof DocumentType);
        $mimeType = $file->getMimeType() ?: $file->getClientMimeType();
        $sizeKilobytes = (int) ceil($file->getSize() / 1024);

        if (! in_array($mimeType, $documentType->allowedMimeTypes(), true)) {
            throw ValidationException::withMessages([
                'file' => 'O tipo de ficheiro não é permitido para este documento.',
            ]);
        }

        if ($sizeKilobytes > $documentType->maxFileSizeKilobytes()) {
            throw ValidationException::withMessages([
                'file' => 'O ficheiro excede o tamanho máximo permitido para este documento.',
            ]);
        }
    }

    private function ensureEditable(AdhesionRegistration $registration): void
    {
        $status = $registration->status;

        if (! in_array($status, [
            AdhesionRegistrationStatus::Incomplete,
            AdhesionRegistrationStatus::Registered,
        ], true)) {
            throw ValidationException::withMessages([
                'document' => 'Não é possível alterar documentos neste estado do Registo de Adesão.',
            ]);
        }
    }

    private function ensureTypeMatches(RequiredDocument $requiredDocument, int $documentTypeId): void
    {
        if ($requiredDocument->document_type_id !== $documentTypeId) {
            throw ValidationException::withMessages([
                'document_type_id' => 'O tipo documental não corresponde à regra selecionada.',
            ]);
        }
    }

    /**
     * @param  array{household_member_id?: int|string|null, income_record_id?: int|string|null}  $data
     */
    private function targetFor(
        AdhesionRegistration $registration,
        DocumentAppliesTo $appliesTo,
        array $data,
        ?Application $application,
    ): Model {
        $registration->loadMissing(['household.members', 'household.incomeRecords', 'currentHousingSituation']);
        $household = $registration->household instanceof Household ? $registration->household : null;
        $housing = $registration->currentHousingSituation instanceof CurrentHousingSituation ? $registration->currentHousingSituation : null;

        return match ($appliesTo) {
            DocumentAppliesTo::AdhesionRegistration, DocumentAppliesTo::General => $registration,
            DocumentAppliesTo::Household => $household ?? throw ValidationException::withMessages(['household_id' => 'Crie primeiro o agregado familiar.']),
            DocumentAppliesTo::HouseholdMember => $this->householdMemberTarget($household, $data['household_member_id'] ?? null),
            DocumentAppliesTo::IncomeRecord => $this->incomeRecordTarget($household, $data['income_record_id'] ?? null),
            DocumentAppliesTo::CurrentHousingSituation => $housing ?? throw ValidationException::withMessages(['current_housing_situation_id' => 'Preencha primeiro a situação habitacional atual.']),
            DocumentAppliesTo::Application => $application
                ?? throw ValidationException::withMessages(['document' => 'Selecione uma candidatura em rascunho válida.']),
            DocumentAppliesTo::Contract => throw ValidationException::withMessages(['document' => 'Este tipo de documento ainda não está disponível nesta fase.']),
        };
    }

    /**
     * @return array<string, int|string|null>
     */
    private function targetColumns(DocumentAppliesTo $appliesTo, Model $target): array
    {
        return match ($appliesTo) {
            DocumentAppliesTo::Household => ['household_id' => $target->getKey()],
            DocumentAppliesTo::HouseholdMember => [
                'household_id' => $target instanceof HouseholdMember ? $target->household_id : null,
                'household_member_id' => $target->getKey(),
            ],
            DocumentAppliesTo::IncomeRecord => [
                'household_id' => $target instanceof IncomeRecord ? $target->household_id : null,
                'household_member_id' => $target instanceof IncomeRecord ? $target->household_member_id : null,
                'income_record_id' => $target->getKey(),
            ],
            DocumentAppliesTo::CurrentHousingSituation => [
                'current_housing_situation_id' => $target->getKey(),
            ],
            DocumentAppliesTo::Application => [
                'application_id' => $target->getKey(),
            ],
            default => [],
        };
    }

    private function ensureNoActiveSubmission(
        AdhesionRegistration $registration,
        RequiredDocument $requiredDocument,
        Model $target,
        ?Application $application,
    ): void {
        $query = $registration->documentSubmissions()
            ->where('required_document_id', $requiredDocument->id)
            ->whereNotIn('status', [
                DocumentStatus::Cancelled->value,
                DocumentStatus::Replaced->value,
            ]);

        if ($application !== null) {
            $query->where('application_id', $application->id);
        }

        match ($requiredDocument->required_for) {
            DocumentAppliesTo::Household => $query->where('household_id', $target->getKey()),
            DocumentAppliesTo::HouseholdMember => $query->where('household_member_id', $target->getKey()),
            DocumentAppliesTo::IncomeRecord => $query->where('income_record_id', $target->getKey()),
            DocumentAppliesTo::CurrentHousingSituation => $query->where('current_housing_situation_id', $target->getKey()),
            DocumentAppliesTo::Application => $query->where('application_id', $target->getKey()),
            default => null,
        };

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'required_document_id' => 'Já existe uma submissão ativa para este documento. Use a substituição.',
            ]);
        }
    }

    /**
     * @param  array{title?: string|null, issue_date?: mixed, expiry_date?: mixed, notes?: string|null}  $data
     * @return array{title: string|null, issue_date: mixed, expiry_date: mixed, notes: string|null}
     */
    private function safeSubmissionData(array $data): array
    {
        return [
            'title' => $data['title'] ?? null,
            'issue_date' => $data['issue_date'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function scheduleDocumentAiAnalysis(DocumentSubmission $submission, User $actor): void
    {
        if (! (bool) config('document-ai.enabled', true)) {
            return;
        }

        DB::afterCommit(function () use ($submission, $actor): void {
            try {
                $freshSubmission = $submission->fresh(['currentVersion']);

                if (! $freshSubmission instanceof DocumentSubmission) {
                    return;
                }

                $analysis = $this->documentAiPipeline->createPendingForDocument($freshSubmission, $actor);
                $this->documentAiPipeline->dispatch($analysis);
            } catch (Throwable $exception) {
                $this->auditLogger->record(
                    event: AuditEvents::UPDATE,
                    auditable: $submission,
                    module: 'documents',
                    action: 'document_ai_pending_failed',
                    description: 'Falha controlada ao preparar análise documental por IA. Upload documental preservado.',
                    metadata: [
                        'actor_id' => $actor->id,
                        'document_submission_id' => $submission->id,
                        'error_class' => $exception::class,
                    ],
                );
            }
        });
    }

    private function applicationFor(AdhesionRegistration $registration, ?string $publicId): ?Application
    {
        if ($publicId === null) {
            return null;
        }

        $application = $registration->applications()
            ->where('public_id', $publicId)
            ->first();

        if ($application === null || ! $application->isEditable()) {
            throw ValidationException::withMessages([
                'application_public_id' => 'A candidatura indicada não está disponível para submissão documental.',
            ]);
        }

        return $application;
    }

    private function householdMemberTarget(?Household $household, mixed $id): HouseholdMember
    {
        $member = $household?->members()->whereKey($id)->first();

        if (! $member instanceof HouseholdMember) {
            throw ValidationException::withMessages(['household_member_id' => 'O membro selecionado não pertence ao seu agregado.']);
        }

        return $member;
    }

    private function incomeRecordTarget(?Household $household, mixed $id): IncomeRecord
    {
        $record = $household?->incomeRecords()->whereKey($id)->first();

        if (! $record instanceof IncomeRecord) {
            throw ValidationException::withMessages(['income_record_id' => 'O rendimento selecionado não pertence ao seu agregado.']);
        }

        return $record;
    }

    private function ensureRuleScope(RequiredDocument $requiredDocument, ?Application $application): void
    {
        if ($requiredDocument->program_id === null && $requiredDocument->contest_id === null) {
            return;
        }

        if ($application === null
            || ($requiredDocument->program_id !== null && $requiredDocument->program_id !== $application->program_id)
            || ($requiredDocument->contest_id !== null && $requiredDocument->contest_id !== $application->contest_id)) {
            throw ValidationException::withMessages([
                'required_document_id' => 'A regra documental não pertence a esta candidatura.',
            ]);
        }
    }
}
