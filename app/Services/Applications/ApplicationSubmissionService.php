<?php

namespace App\Services\Applications;

use App\Enums\ApplicationDeclarationType;
use App\Enums\ApplicationStatus;
use App\Enums\RegulatoryContext;
use App\Models\Application;
use App\Models\User;
use App\Services\Allocation\HousingPreferenceService;
use App\Services\Audit\AuditLogger;
use App\Services\Regulatory\AffordableRentLegalRegimeResolver;
use App\Services\Regulatory\RegulatorySnapshotService;
use App\Support\AuditEvents;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        private readonly AffordableRentLegalRegimeResolver $regimeResolver,
        private readonly RegulatorySnapshotService $regulatorySnapshotService,
        private readonly HousingPreferenceService $housingPreferences,
    ) {}

    public function submit(Application $application, User $actor): Application
    {
        try {
            return DB::transaction(function () use ($application, $actor): Application {
                $acceptedAt = now();
                $lockedApplication = Application::query()
                    ->whereKey($application->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $lockedApplication->loadMissing(['contest', 'program']);
                $this->validationService->validateSubmission($lockedApplication);

                $profile = $this->regimeResolver
                    ->profileForApplication($lockedApplication);

                if ($profile !== null) {
                    $this->regulatorySnapshotService->attach(
                        $lockedApplication,
                        $profile,
                        RegulatoryContext::ApplicationSubmission,
                        $acceptedAt,
                        $actor,
                        'application_submission',
                    );
                    $lockedApplication->unsetRelation('regulatorySnapshot');
                    $lockedApplication->refresh();
                }

                $this->housingPreferences->revalidateAndLockForSubmission(
                    $lockedApplication,
                    $actor,
                    $acceptedAt,
                );

                $lockedApplication->forceFill([
                    'application_number' => $this->numberService
                        ->generate($lockedApplication),
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

                $this->storeDeclarations($lockedApplication, $acceptedAt);
                $documents = $this->documentService->associate($lockedApplication);

                $from = $lockedApplication->status;
                $lockedApplication
                    ->forceFill(['status' => ApplicationStatus::Submitted])
                    ->save();

                $lockedApplication->load([
                    'adhesionRegistration',
                    'household.members.incomeRecords.incomeSource',
                    'household.incomeRecords.incomeSource',
                    'currentHousingSituation',
                    'applicationDocuments.documentSubmission.currentVersion',
                    'applicationDocuments.documentType',
                    'housingPreferences.housingUnit',
                    'contest',
                    'program',
                ]);
                $this->snapshotService->create($lockedApplication);
                $this->applicationService->recordStatus(
                    $lockedApplication,
                    $from,
                    ApplicationStatus::Submitted,
                    $actor,
                );

                $this->auditLogger->record(
                    event: AuditEvents::CREATE,
                    auditable: $lockedApplication,
                    module: 'applications',
                    action: 'submit',
                    description: 'Candidatura submetida formalmente.',
                    oldValues: ['status' => $from->value],
                    newValues: ['status' => ApplicationStatus::Submitted->value],
                    metadata: [
                        'application_number' => $lockedApplication->application_number,
                        'document_count' => $documents->count(),
                        'housing_preferences_count' => $lockedApplication
                            ->housingPreferences
                            ->count(),
                        'declaration_version' => self::DECLARATION_VERSION,
                    ],
                );

                $lockedApplication->load([
                    'contest.program',
                    'statusHistories.changedBy',
                    'snapshots',
                    'applicationDocuments.documentType',
                    'housingPreferences.housingUnit',
                    'declarations',
                ]);

                return $lockedApplication;
            });
        } catch (ValidationException $exception) {
            if (array_key_exists('preferences', $exception->errors())) {
                $this->auditLogger->record(
                    AuditEvents::UPDATE,
                    $application,
                    'allocations',
                    'housing_preference_rejected_on_submission',
                    'Submissão recusada por preferência habitacional inválida.',
                    metadata: [
                        'application_id' => $application->id,
                        'actor_id' => $actor->id,
                    ],
                );
            }

            throw $exception;
        }
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
