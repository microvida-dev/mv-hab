<?php

namespace App\Services\Applications;

use App\Enums\ApplicationDeclarationType;
use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ApplicationSubmissionService
{
    public const DECLARATION_VERSION = '2026-06-11.v1';

    public function __construct(
        private readonly ApplicationValidationService $validationService,
        private readonly ApplicationDocumentService $documentService,
        private readonly ApplicationSnapshotService $snapshotService,
        private readonly ApplicationNumberService $numberService,
        private readonly ApplicationService $applicationService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function submit(Application $application, User $actor): Application
    {
        $this->validationService->validateSubmission($application);

        return DB::transaction(function () use ($application, $actor) {
            $acceptedAt = now();
            $application->loadMissing(['contest', 'program']);
            $application->forceFill([
                'application_number' => $this->numberService->generate($application),
                'declaration_accepted' => true,
                'declaration_accepted_at' => $acceptedAt,
                'contest_rules_accepted' => true,
                'contest_rules_accepted_at' => $acceptedAt,
                'data_processing_accepted' => true,
                'data_processing_accepted_at' => $acceptedAt,
                'truthfulness_accepted' => true,
                'truthfulness_accepted_at' => $acceptedAt,
                'data_current_confirmed' => true,
                'data_current_confirmed_at' => $acceptedAt,
                'submitted_at' => $acceptedAt,
                'locked_at' => $acceptedAt,
                'updated_by' => $actor->id,
            ])->save();

            $this->storeDeclarations($application, $acceptedAt);
            $documents = $this->documentService->associate($application);

            $from = $application->status;
            $application->forceFill(['status' => ApplicationStatus::Submitted])->save();
            $application->load([
                'adhesionRegistration',
                'household.members.incomeRecords.incomeSource',
                'household.incomeRecords.incomeSource',
                'currentHousingSituation',
                'applicationDocuments.documentSubmission.currentVersion',
                'applicationDocuments.documentType',
                'contest',
                'program',
            ]);
            $this->snapshotService->create($application);
            $this->applicationService->recordStatus(
                $application,
                $from,
                ApplicationStatus::Submitted,
                $actor,
            );

            $this->auditLogger->record(
                event: AuditEvents::CREATE,
                auditable: $application,
                module: 'applications',
                action: 'submit',
                description: 'Candidatura submetida formalmente.',
                oldValues: ['status' => $from->value],
                newValues: ['status' => ApplicationStatus::Submitted->value],
                metadata: [
                    'application_number' => $application->application_number,
                    'document_count' => $documents->count(),
                    'declaration_version' => self::DECLARATION_VERSION,
                ],
            );

            $application->load([
                'contest.program',
                'statusHistories.changedBy',
                'snapshots',
                'applicationDocuments.documentType',
                'declarations',
            ]);

            return $application;
        });
    }

    private function storeDeclarations(Application $application, Carbon $acceptedAt): void
    {
        foreach (ApplicationDeclarationType::cases() as $type) {
            $application->declarations()->updateOrCreate(
                ['declaration_type' => $type->value],
                [
                    'accepted' => true,
                    'accepted_at' => $acceptedAt,
                    'text_version' => self::DECLARATION_VERSION,
                ],
            );
        }
    }
}
