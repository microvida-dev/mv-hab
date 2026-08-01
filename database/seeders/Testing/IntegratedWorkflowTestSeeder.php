<?php

namespace Database\Seeders\Testing;

use App\Enums\AdministrativeProcessStatus;
use App\Enums\AllocationMethod;
use App\Enums\AllocationStatus;
use App\Enums\ApplicationReviewBatchCycle;
use App\Enums\ApplicationReviewBatchOutcome;
use App\Enums\ApplicationReviewBatchStatus;
use App\Enums\ApplicationReviewResult;
use App\Enums\ApplicationScoreStatus;
use App\Enums\ApplicationStatus;
use App\Enums\ArrearStatus;
use App\Enums\ComplaintStatus;
use App\Enums\ContestDeadlineType;
use App\Enums\ContractStatus;
use App\Enums\CorrectionRequestItemStatus;
use App\Enums\CorrectionRequestStatus;
use App\Enums\CorrectionResponseKind;
use App\Enums\CorrectionResponseReviewResult;
use App\Enums\CorrectionResponseStatus;
use App\Enums\CorrectionRevalidationAggregateResult;
use App\Enums\DefinitiveListStatus;
use App\Enums\DocumentStatus;
use App\Enums\EligibilityCheckType;
use App\Enums\EligibilityResult;
use App\Enums\LeasePaymentStatus;
use App\Enums\ListEntryStatus;
use App\Enums\MaintenanceRequestStatus;
use App\Enums\ProvisionalListStatus;
use App\Enums\RankingSnapshotStatus;
use App\Enums\RentCalculationMethod;
use App\Enums\RentCalculationStatus;
use App\Enums\RentInstallmentStatus;
use App\Enums\RentRuleSetStatus;
use App\Enums\RentScheduleStatus;
use App\Enums\ScoringRunStatus;
use App\Models\AdhesionRegistration;
use App\Models\AdministrativeProcess;
use App\Models\Allocation;
use App\Models\AllocationRuleSet;
use App\Models\AllocationRun;
use App\Models\Application;
use App\Models\ApplicationReviewBatch;
use App\Models\ApplicationReviewBatchItem;
use App\Models\ApplicationScore;
use App\Models\Arrear;
use App\Models\AuditEvent;
use App\Models\Complaint;
use App\Models\Contest;
use App\Models\ContestDeadline;
use App\Models\ContestHousingUnit;
use App\Models\Contract;
use App\Models\CorrectionRequest;
use App\Models\CorrectionResponse;
use App\Models\CurrentHousingSituation;
use App\Models\DefinitiveList;
use App\Models\DefinitiveListEntry;
use App\Models\DocumentSubmission;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\EligibilityCheck;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\HousingUnit;
use App\Models\IncomeRecord;
use App\Models\LeasePayment;
use App\Models\MaintenanceRequest;
use App\Models\Municipality;
use App\Models\OfficialNotification;
use App\Models\Program;
use App\Models\ProvisionalList;
use App\Models\ProvisionalListEntry;
use App\Models\RankingEntry;
use App\Models\RankingSnapshot;
use App\Models\RentCalculation;
use App\Models\RentInstallment;
use App\Models\RentRuleSet;
use App\Models\RentSchedule;
use App\Models\ReportExport;
use App\Models\RequiredDocument;
use App\Models\ScoringRuleSet;
use App\Models\ScoringRun;
use App\Models\TenantFinancialAccount;
use App\Models\User;
use App\Services\Administrative\ApplicationReviewPublicationService;
use App\Services\Administrative\CorrectionDifferentialResolver;
use App\Services\Administrative\CorrectionResolutionService;
use App\Services\Administrative\CorrectionRevalidationService;
use App\Services\Administrative\CorrectionSubmissionService;
use App\Services\Support\CanonicalJsonHasher;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use LogicException;

class IntegratedWorkflowTestSeeder extends Seeder
{
    private Municipality $municipality;

    private Program $program;

    private Contest $contest;

    private User $administrator;

    public function run(): void
    {
        $this->call(SystemAccessSeeder::class);
        $this->municipality = Municipality::factory()->create();

        $this->administrator = $this->user('s19-admin@example.test', 'Administrador QA Sprint 19', 'administrator');
        $technician = $this->user('s19-tecnico@example.test', 'Tecnico Municipal QA Sprint 19', 'municipal_technician');
        $auditor = $this->user('s19-auditor@example.test', 'Auditor QA Sprint 19', 'auditor');
        $this->user('s19-financeiro@example.test', 'Gestor Financeiro QA Sprint 19', 'financial_manager');
        $this->user('s19-manutencao@example.test', 'Gestor Manutencao QA Sprint 19', 'maintenance_manager');

        $this->program = Program::factory()->published()->create([
            'municipality_id' => $this->municipality->id,
            'name' => 'Programa QA Integrado Sprint 19',
            'slug' => 'programa-qa-integrado-sprint-19',
            'description' => 'Programa fictício para testes integrados de qualidade.',
        ]);
        $this->contest = Contest::factory()->for($this->program)->open()->create([
            'title' => 'Concurso QA Integrado Sprint 19',
            'slug' => 'concurso-qa-integrado-sprint-19',
            'description' => 'Concurso fictício para testes integrados de qualidade.',
        ]);

        $eligible = $this->candidateScenario('eligible', ApplicationStatus::Submitted, EligibilityResult::Eligible, AdministrativeProcessStatus::AdmittedForScoring);
        $this->candidateScenario('ineligible', ApplicationStatus::Ineligible, EligibilityResult::Ineligible, AdministrativeProcessStatus::NotAdmitted);
        $this->candidateScenario('document-rejected', ApplicationStatus::Submitted, EligibilityResult::RequiresReview, AdministrativeProcessStatus::DocumentReview, DocumentStatus::Rejected);
        $correction = $this->candidateScenario('correction', ApplicationStatus::RequiresCorrection, EligibilityResult::RequiresReview, AdministrativeProcessStatus::RequiresCorrection);
        $admitted = $this->candidateScenario('admitted', ApplicationStatus::Submitted, EligibilityResult::Eligible, AdministrativeProcessStatus::AdmittedForScoring);
        $this->candidateScenario('excluded', ApplicationStatus::Excluded, EligibilityResult::Ineligible, AdministrativeProcessStatus::NotAdmitted);
        $complaintAccepted = $this->candidateScenario('complaint-accepted', ApplicationStatus::Submitted, EligibilityResult::Eligible, AdministrativeProcessStatus::AdmittedForScoring);
        $allocated = $this->candidateScenario('allocated', ApplicationStatus::Submitted, EligibilityResult::Eligible, AdministrativeProcessStatus::AdmittedForScoring);
        $tenant = $this->candidateScenario('tenant-arrear', ApplicationStatus::Submitted, EligibilityResult::Eligible, AdministrativeProcessStatus::AdmittedForScoring);
        $maintenance = $this->candidateScenario('tenant-maintenance', ApplicationStatus::Submitted, EligibilityResult::Eligible, AdministrativeProcessStatus::AdmittedForScoring);

        CorrectionRequest::factory()->create([
            'administrative_process_id' => $correction['process']->id,
            'application_id' => $correction['application']->id,
            'user_id' => $correction['user']->id,
            'status' => CorrectionRequestStatus::Notified,
            'candidate_visible' => true,
        ]);
        $this->seedDifferentialRevalidationScenario();

        $scoringRuleSet = ScoringRuleSet::factory()->active()->for($this->program)->for($this->contest)->create();
        $scoringRun = ScoringRun::factory()->create([
            'scoring_rule_set_id' => $scoringRuleSet->id,
            'program_id' => $this->program->id,
            'contest_id' => $this->contest->id,
            'status' => ScoringRunStatus::Completed->value,
            'started_by' => $technician->id,
            'started_at' => now()->subMinutes(20),
            'completed_at' => now()->subMinutes(10),
            'total_applications' => 6,
            'scored_applications' => 4,
        ]);

        foreach ([$eligible, $admitted, $complaintAccepted, $allocated] as $position => $scenario) {
            $score = ApplicationScore::factory()->create([
                'scoring_run_id' => $scoringRun->id,
                'application_id' => $scenario['application']->id,
                'scoring_rule_set_id' => $scoringRuleSet->id,
                'program_id' => $this->program->id,
                'contest_id' => $this->contest->id,
                'user_id' => $scenario['user']->id,
                'status' => ApplicationScoreStatus::Calculated->value,
                'total_score' => 90 - ($position * 7),
                'rank_position' => $position + 1,
            ]);
        }

        $ranking = RankingSnapshot::factory()->create([
            'scoring_run_id' => $scoringRun->id,
            'program_id' => $this->program->id,
            'contest_id' => $this->contest->id,
            'status' => RankingSnapshotStatus::Locked->value,
            'generated_by' => $technician->id,
            'published_at' => now(),
        ]);
        RankingEntry::factory()->create([
            'ranking_snapshot_id' => $ranking->id,
            'application_id' => $eligible['application']->id,
            'application_score_id' => ApplicationScore::query()->where('application_id', $eligible['application']->id)->value('id'),
            'rank_position' => 1,
            'total_score' => 90,
        ]);

        $provisionalList = ProvisionalList::factory()->complaintOpen()->create([
            'ranking_snapshot_id' => $ranking->id,
            'status' => ProvisionalListStatus::ComplaintPeriodOpen->value,
        ]);
        ProvisionalListEntry::factory()->create([
            'provisional_list_id' => $provisionalList->id,
            'application_id' => $complaintAccepted['application']->id,
            'status' => ListEntryStatus::Ranked->value,
            'rank_position' => 3,
        ]);
        Complaint::factory()->create([
            'provisional_list_id' => $provisionalList->id,
            'application_id' => $complaintAccepted['application']->id,
            'user_id' => $complaintAccepted['user']->id,
            'status' => ComplaintStatus::Accepted->value,
            'submitted_at' => now()->subDays(2),
            'review_completed_at' => now()->subDay(),
        ]);

        $definitiveList = DefinitiveList::factory()->create([
            'provisional_list_id' => $provisionalList->id,
            'status' => DefinitiveListStatus::Published->value,
            'published_at' => now(),
            'public_visibility' => true,
        ]);
        $definitiveEntry = DefinitiveListEntry::factory()->create([
            'definitive_list_id' => $definitiveList->id,
            'application_id' => $allocated['application']->id,
            'rank_position' => 1,
            'changed_after_complaint' => true,
        ]);

        $housingUnit = HousingUnit::factory()->create([
            'code' => 'S19-HAB-001',
            'address' => 'Rua Fictícia QA, 1',
            'typology' => 'T2',
            'bedrooms' => 2,
        ]);
        $contestHousingUnit = ContestHousingUnit::factory()->create([
            'program_id' => $this->program->id,
            'contest_id' => $this->contest->id,
            'housing_unit_id' => $housingUnit->id,
            'typology' => 'T2',
            'bedrooms' => 2,
            'monthly_rent' => 300,
        ]);
        $allocationRuleSet = AllocationRuleSet::factory()->create([
            'program_id' => $this->program->id,
            'contest_id' => $this->contest->id,
            'created_by' => $technician->id,
            'updated_by' => $technician->id,
        ]);
        $allocationRun = AllocationRun::factory()->create([
            'allocation_rule_set_id' => $allocationRuleSet->id,
            'program_id' => $this->program->id,
            'contest_id' => $this->contest->id,
            'definitive_list_id' => $definitiveList->id,
            'started_by' => $technician->id,
        ]);
        $allocation = Allocation::factory()->create([
            'allocation_run_id' => $allocationRun->id,
            'allocation_rule_set_id' => $allocationRuleSet->id,
            'program_id' => $this->program->id,
            'contest_id' => $this->contest->id,
            'definitive_list_id' => $definitiveList->id,
            'definitive_list_entry_id' => $definitiveEntry->id,
            'application_id' => $allocated['application']->id,
            'user_id' => $allocated['user']->id,
            'contest_housing_unit_id' => $contestHousingUnit->id,
            'housing_unit_id' => $housingUnit->id,
            'allocation_method' => AllocationMethod::Ranking->value,
            'status' => AllocationStatus::ReadyForContract->value,
            'rank_position' => 1,
            'accepted_at' => now(),
            'ready_for_contract_at' => now(),
            'allocated_by' => $technician->id,
        ]);

        $rentRuleSet = RentRuleSet::factory()->create([
            'program_id' => $this->program->id,
            'contest_id' => $this->contest->id,
            'status' => RentRuleSetStatus::Active->value,
            'calculation_method' => RentCalculationMethod::EffortRate->value,
            'effort_rate_percentage' => 30,
            'minimum_rent' => 100,
            'maximum_rent' => 400,
            'deposit_months' => 2,
            'created_by' => $technician->id,
            'updated_by' => $technician->id,
        ]);
        $rentCalculation = RentCalculation::factory()->create([
            'rent_rule_set_id' => $rentRuleSet->id,
            'allocation_id' => $allocation->id,
            'application_id' => $allocated['application']->id,
            'user_id' => $allocated['user']->id,
            'household_id' => $allocated['household']->id,
            'housing_unit_id' => $housingUnit->id,
            'contest_housing_unit_id' => $contestHousingUnit->id,
            'status' => RentCalculationStatus::Approved->value,
            'monthly_household_income' => 1000,
            'annual_household_income' => 12000,
            'applicable_rent' => 300,
            'deposit_amount' => 600,
            'approved_by' => $technician->id,
        ]);

        $contract = new Contract;
        $contract->forceFill([
            'contract_number' => 'CTR-S19-000001',
            'housing_unit_id' => $housingUnit->id,
            'program_id' => $this->program->id,
            'contest_id' => $this->contest->id,
            'application_id' => $allocated['application']->id,
            'allocation_id' => $allocation->id,
            'user_id' => $allocated['user']->id,
            'household_id' => $allocated['household']->id,
            'contest_housing_unit_id' => $contestHousingUnit->id,
            'rent_calculation_id' => $rentCalculation->id,
            'tenant_name' => 'Arrendatário QA Sprint 19',
            'tenant_email' => 's19-allocated@example.test',
            'housing_address' => 'Rua Fictícia QA, 1',
            'housing_typology' => 'T2',
            'start_date' => today()->subMonth(),
            'end_date' => today()->addMonths(59),
            'duration_months' => 60,
            'monthly_rent' => 300,
            'deposit_amount' => 600,
            'payment_day' => 8,
            'status' => ContractStatus::Active->value,
            'issued_at' => now()->subMonth(),
            'signed_at' => now()->subMonth()->addDays(2),
            'activated_at' => now()->subMonth()->addDays(3),
            'created_by' => $technician->id,
            'updated_by' => $technician->id,
        ])->save();

        $account = TenantFinancialAccount::factory()->create([
            'lease_contract_id' => $contract->id,
            'application_id' => $allocated['application']->id,
            'allocation_id' => $allocation->id,
            'user_id' => $tenant['user']->id,
            'household_id' => $tenant['household']->id,
            'housing_unit_id' => $housingUnit->id,
            'account_number' => 'ACC-S19-000001',
            'current_balance' => 300,
            'total_issued' => 600,
            'total_paid' => 300,
            'total_overdue' => 300,
            'next_due_date' => today()->subDays(18),
        ]);
        $schedule = RentSchedule::factory()->create([
            'tenant_financial_account_id' => $account->id,
            'lease_contract_id' => $contract->id,
            'user_id' => $tenant['user']->id,
            'status' => RentScheduleStatus::Active->value,
            'monthly_rent' => 300,
        ]);
        $installment = RentInstallment::factory()->create([
            'tenant_financial_account_id' => $account->id,
            'rent_schedule_id' => $schedule->id,
            'lease_contract_id' => $contract->id,
            'user_id' => $tenant['user']->id,
            'status' => RentInstallmentStatus::Overdue->value,
            'due_date' => today()->subDays(18),
            'amount_due' => 300,
            'amount_paid' => 0,
            'amount_outstanding' => 300,
            'overdue_at' => now()->subDays(18),
        ]);
        LeasePayment::factory()->create([
            'tenant_financial_account_id' => $account->id,
            'lease_contract_id' => $contract->id,
            'user_id' => $tenant['user']->id,
            'status' => LeasePaymentStatus::Confirmed->value,
            'amount' => 300,
            'allocated_amount' => 300,
            'unallocated_amount' => 0,
        ]);
        Arrear::factory()->create([
            'tenant_financial_account_id' => $account->id,
            'lease_contract_id' => $contract->id,
            'rent_installment_id' => $installment->id,
            'user_id' => $tenant['user']->id,
            'status' => ArrearStatus::Open->value,
            'outstanding_amount' => 300,
        ]);
        MaintenanceRequest::factory()->create([
            'housing_unit_id' => $housingUnit->id,
            'lease_contract_id' => $contract->id,
            'application_id' => $maintenance['application']->id,
            'user_id' => $maintenance['user']->id,
            'status' => MaintenanceRequestStatus::UnderReview->value,
            'request_number' => 'MAN-S19-000001',
            'title' => 'Pedido fictício de manutenção Sprint 19',
        ]);

        OfficialNotification::factory()->create([
            'user_id' => $eligible['user']->id,
            'subject' => 'Notificação crítica fictícia Sprint 19',
            'body' => 'Conteúdo fictício para validação integrada.',
        ]);
        ReportExport::factory()->create([
            'user_id' => $auditor->id,
            'file_path' => 'reports/testing/s19/quality-export.csv',
        ]);
        AuditEvent::factory()->create([
            'user_id' => $technician->id,
            'event_code' => 'sprint19.integrated_seed.created',
            'description' => 'Seeder integrado Sprint 19 executado com dados fictícios.',
        ]);
    }

    private function user(string $email, string $name, string $role): User
    {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'municipality_id' => $this->municipality->id,
                'name' => $name,
                'password' => Hash::make(Str::random(40)),
                'email_verified_at' => now(),
            ],
        );
        $user->forceFill([
            'municipality_id' => $this->municipality->id,
        ])->save();
        $user->assignRole($role);

        return $user;
    }

    private function seedDifferentialRevalidationScenario(): void
    {
        $scenario = $this->candidateScenario(
            'correction-revalidation',
            ApplicationStatus::Submitted,
            EligibilityResult::RequiresReview,
            AdministrativeProcessStatus::DocumentReview,
        );
        $application = $scenario['application'];
        $process = $scenario['process'];
        $candidate = $scenario['user'];
        $documentType = DocumentType::factory()->create([
            'name' => 'Documento fictício para revalidação diferencial',
        ]);
        $requiredDocument = RequiredDocument::factory()->create([
            'document_type_id' => $documentType->id,
            'program_id' => $this->program->id,
            'contest_id' => $this->contest->id,
        ]);
        ContestDeadline::query()->updateOrCreate(
            [
                'contest_id' => $this->contest->id,
                'type' => ContestDeadlineType::Corrections->value,
            ],
            [
                'label' => ContestDeadlineType::Corrections->defaultLabel(),
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addWeeks(2),
                'sort_order' => 30,
            ],
        );
        $payload = [
            'schema_version' => 2,
            'process' => [
                'id' => $process->id,
                'number' => $process->process_number,
                'status' => $process->status->value,
                'assigned_to' => $this->administrator->id,
                'application_id' => $application->id,
                'contest_id' => $application->contest_id,
                'program_id' => $application->program_id,
            ],
            'application' => [
                'id' => $application->id,
                'public_id' => $application->public_id,
                'number' => $application->application_number,
                'status' => $application->status->value,
                'submitted_at' => $application->submitted_at?->toIso8601String(),
                'program_id' => $application->program_id,
                'contest_id' => $application->contest_id,
                'legal_regime' => null,
                'regulatory_snapshot_id' => null,
            ],
            'outcome' => ApplicationReviewBatchOutcome::CorrectionRequired->value,
            'technical_result' => ApplicationReviewResult::RequiresCorrection->value,
            'review' => null,
            'readiness' => [
                'ready' => false,
                'missing' => 1,
                'blockers' => ['1 elemento obrigatório em falta'],
            ],
            'documents' => [],
            'findings' => [[
                'finding_status' => 'missing',
                'document_status' => DocumentStatus::Missing->value,
                'target_type' => $application->getMorphClass(),
                'target_id' => $application->id,
                'target_label' => $application->application_number,
                'document_type_id' => $documentType->id,
                'required_document_id' => $requiredDocument->id,
                'source_document_submission_id' => null,
                'requirement_instance' => 1,
                'title' => 'Elemento fictício para revalidação diferencial',
                'description' => 'Submeter o elemento fictício solicitado.',
                'is_required' => true,
                'sort_order' => 1,
            ]],
        ];
        $hasher = app(CanonicalJsonHasher::class);
        $snapshotHash = $hasher->hash($payload);
        $batchHash = $hasher->hash([
            'schema_version' => 1,
            'contest_id' => $application->contest_id,
            'cycle' => ApplicationReviewBatchCycle::InitialReview->value,
            'items' => [[
                'application_id' => $application->id,
                'snapshot_hash' => $snapshotHash,
                'payload' => $payload,
            ]],
        ]);
        $batch = new ApplicationReviewBatch([
            'municipality_id' => $this->municipality->id,
            'contest_id' => $this->contest->id,
            'cycle' => ApplicationReviewBatchCycle::InitialReview,
            'sequence_number' => 53001,
            'status' => ApplicationReviewBatchStatus::Sealed,
            'reason' => 'Fixture integrada da revalidação diferencial 53F.',
            'item_count' => 1,
            'seal_key' => hash('sha256', 'sprint53f-initial-seal-'.$application->id),
            'source_fingerprint' => hash('sha256', 'sprint53f-initial-source-'.$application->id),
            'snapshot_hash' => $batchHash,
        ]);
        $batch->forceFill([
            'sealed_by' => $this->administrator->id,
            'sealed_at' => now(),
        ])->save();
        ApplicationReviewBatchItem::query()->create([
            'application_review_batch_id' => $batch->id,
            'administrative_process_id' => $process->id,
            'application_id' => $application->id,
            'application_review_id' => null,
            'process_number' => $process->process_number,
            'application_number' => $application->application_number,
            'application_public_id' => $application->public_id,
            'outcome' => ApplicationReviewBatchOutcome::CorrectionRequired,
            'technical_result' => ApplicationReviewResult::RequiresCorrection->value,
            'review_lock_version' => 1,
            'readiness_snapshot' => $payload['readiness'],
            'document_snapshot' => [],
            'snapshot_payload' => $payload,
            'source_fingerprint' => hash('sha256', 'sprint53f-initial-item-'.$application->id),
            'snapshot_hash' => $snapshotHash,
        ]);

        Auth::login($this->administrator);
        $publicationService = app(ApplicationReviewPublicationService::class);
        $publicationReason = 'Publicação inicial da fixture integrada 53F.';
        $publicationPreview = $publicationService->preview(
            $batch->refresh()->load(['contest.program', 'items']),
            $this->administrator,
            $publicationReason,
        );
        $publicationService->publish(
            $batch->refresh(),
            $this->administrator,
            [
                'reason' => $publicationReason,
                'preview_token' => $publicationPreview['token'],
            ],
        );
        $request = CorrectionRequest::query()
            ->where('application_id', $application->id)
            ->whereNotNull('application_review_publication_result_id')
            ->sole();
        $requestItem = $request->items()->sole();
        $submission = DocumentSubmission::factory()->create([
            'document_type_id' => $documentType->id,
            'required_document_id' => $requiredDocument->id,
            'requirement_instance' => 1,
            'user_id' => $candidate->id,
            'adhesion_registration_id' => $application->adhesion_registration_id,
            'application_id' => $application->id,
            'status' => DocumentStatus::Submitted,
            'title' => 'Documento fictício de resposta 53F',
            'original_filename' => 'documento-ficticio-53f.pdf',
            'storage_path' => 'documents/testing/s53f/documento-ficticio-53f.pdf',
            'submitted_by' => $candidate->id,
        ]);
        $version = DocumentVersion::factory()->create([
            'document_submission_id' => $submission->id,
            'version_number' => 1,
            'original_filename' => 'documento-ficticio-53f.pdf',
            'storage_path' => $submission->storage_path,
            'uploaded_by' => $candidate->id,
            'status_at_upload' => DocumentStatus::Submitted,
        ]);
        $submission->forceFill(['current_version_id' => $version->id])->save();
        $response = new CorrectionResponse;
        $response->forceFill([
            'correction_request_id' => $request->id,
            'correction_request_item_id' => $requestItem->id,
            'application_id' => $application->id,
            'user_id' => $candidate->id,
            'document_submission_id' => $submission->id,
            'document_version_id' => $version->id,
            'response_text' => null,
            'response_kind' => CorrectionResponseKind::Document,
            'status' => CorrectionResponseStatus::Draft,
            'prepared_at' => now(),
        ])->save();
        $requestItem->forceFill([
            'status' => CorrectionRequestItemStatus::Responded,
        ])->save();

        Auth::login($candidate);
        app(CorrectionSubmissionService::class)->submit(
            $request->refresh(),
            $candidate,
        );
        Auth::login($this->administrator);
        $revalidation = app(CorrectionRevalidationService::class);
        $revalidation->start($request->refresh(), $this->administrator);
        $differentialItem = app(CorrectionDifferentialResolver::class)
            ->resolve($request->refresh())
            ->reviewableItems()[0];
        $revalidation->decide(
            request: $request->refresh(),
            response: $response->refresh(),
            result: CorrectionResponseReviewResult::Accepted,
            reviewNotes: 'Elemento fictício aceite na segunda análise.',
            sourceFingerprint: $differentialItem->sourceFingerprint,
            expectedDecisionToken: null,
            actor: $this->administrator,
        );
        $resolution = app(CorrectionResolutionService::class);
        $resolutionPreview = $resolution->preview(
            $request->refresh(),
            $this->administrator,
            'Fecho da fixture integrada de revalidação 53F.',
        );
        $revalidationBatch = $resolution->seal(
            $request->refresh(),
            $this->administrator,
            $resolutionPreview['reason'],
            $resolutionPreview['token'],
        );
        $finalReason = 'Publicação final da fixture integrada 53F.';
        $finalPreview = $publicationService->preview(
            $revalidationBatch->refresh()->load(['contest.program', 'items']),
            $this->administrator,
            $finalReason,
        );
        $finalPublication = $publicationService->publish(
            $revalidationBatch->refresh(),
            $this->administrator,
            [
                'reason' => $finalReason,
                'preview_token' => $finalPreview['token'],
            ],
        );
        $request->refresh();
        $process->refresh();

        if (
            $request->status !== CorrectionRequestStatus::Resolved
            || $request->revalidation_result !== CorrectionRevalidationAggregateResult::Accepted
            || $process->status !== AdministrativeProcessStatus::EligibilityReview
            || $finalPublication->results()->count() !== 1
        ) {
            throw new LogicException(
                'A fixture integrada 53F não concluiu o ciclo canónico esperado.',
            );
        }

        Auth::logout();
    }

    /**
     * @return array{user: User, registration: AdhesionRegistration, household: Household, application: Application, process: AdministrativeProcess}
     */
    private function candidateScenario(
        string $code,
        ApplicationStatus $applicationStatus,
        EligibilityResult $eligibilityResult,
        AdministrativeProcessStatus $processStatus,
        DocumentStatus $documentStatus = DocumentStatus::Validated,
    ): array {
        $email = 's19-'.$code.'@example.test';
        $user = $this->user($email, 'Candidato QA '.Str::title(str_replace('-', ' ', $code)), 'candidate');
        $registration = AdhesionRegistration::factory()->registered()->for($user)->create([
            'full_name' => 'Candidato QA '.Str::title(str_replace('-', ' ', $code)),
            'email' => $email,
            'nif' => 'S19'.fake()->unique()->numerify('######'),
        ]);
        $household = Household::factory()->candidate($registration)->create(['members_count' => 1]);
        $member = HouseholdMember::factory()->applicant()->create([
            'household_id' => $household->id,
            'adhesion_registration_id' => $registration->id,
            'full_name' => 'Membro QA '.Str::title(str_replace('-', ' ', $code)),
            'nif' => 'M19'.fake()->unique()->numerify('######'),
            'birth_date' => today()->subYears(34),
        ]);
        IncomeRecord::factory()->create([
            'household_member_id' => $member->id,
            'household_id' => $household->id,
            'adhesion_registration_id' => $registration->id,
            'monthly_amount' => 1000,
            'annual_amount' => 12000,
        ]);
        $housing = CurrentHousingSituation::factory()->create([
            'adhesion_registration_id' => $registration->id,
            'current_monthly_rent' => 450,
        ]);
        $application = Application::factory()->submitted()->create([
            'user_id' => $user->id,
            'adhesion_registration_id' => $registration->id,
            'program_id' => $this->program->id,
            'contest_id' => $this->contest->id,
            'household_id' => $household->id,
            'current_housing_situation_id' => $housing->id,
            'status' => $applicationStatus->value,
        ]);
        EligibilityCheck::factory()->create([
            'program_id' => $this->program->id,
            'contest_id' => $this->contest->id,
            'application_id' => $application->id,
            'adhesion_registration_id' => $registration->id,
            'user_id' => $user->id,
            'check_type' => EligibilityCheckType::ApplicationFormalCheck->value,
            'result' => $eligibilityResult->value,
        ]);
        $process = AdministrativeProcess::factory()->create([
            'application_id' => $application->id,
            'program_id' => $this->program->id,
            'contest_id' => $this->contest->id,
            'user_id' => $user->id,
            'status' => $processStatus->value,
            'assigned_to' => $this->administrator->id,
        ]);

        $documentType = DocumentType::query()->first() ?? DocumentType::factory()->create();
        $submission = DocumentSubmission::factory()->create([
            'document_type_id' => $documentType->id,
            'user_id' => $user->id,
            'adhesion_registration_id' => $registration->id,
            'application_id' => $application->id,
            'status' => $documentStatus->value,
            'storage_path' => 'documents/testing/s19/'.$registration->id.'/identificacao-'.$code.'.pdf',
            'submitted_by' => $user->id,
            'reviewed_by' => $documentStatus === DocumentStatus::Rejected ? $this->administrator->id : null,
            'rejection_reason' => $documentStatus === DocumentStatus::Rejected ? 'Documento fictício rejeitado para cenário de QA.' : null,
        ]);
        $version = DocumentVersion::factory()->create([
            'document_submission_id' => $submission->id,
            'storage_path' => $submission->storage_path,
            'uploaded_by' => $user->id,
            'status_at_upload' => $documentStatus->value,
        ]);
        $submission->forceFill(['current_version_id' => $version->id])->save();

        return compact('user', 'registration', 'household', 'application', 'process');
    }
}
