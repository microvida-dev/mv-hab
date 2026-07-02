<?php

namespace Database\Seeders\Pilot;

use App\Enums\AdhesionRegistrationStatus;
use App\Enums\AdministrativeProcessStatus;
use App\Enums\AllocationMethod;
use App\Enums\AllocationRunStatus;
use App\Enums\AllocationStatus;
use App\Enums\ApplicationScoreStatus;
use App\Enums\ApplicationStatus;
use App\Enums\ComplaintStatus;
use App\Enums\ContractStatus;
use App\Enums\DataSubjectRequestStatus;
use App\Enums\DataSubjectRequestType;
use App\Enums\DefinitiveListStatus;
use App\Enums\DocumentAiClassificationStatus;
use App\Enums\DocumentAiOcrStatus;
use App\Enums\DocumentAiRiskFlagCode;
use App\Enums\DocumentAiRiskSeverity;
use App\Enums\DocumentAiScoreLabel;
use App\Enums\DocumentAiStatus;
use App\Enums\DocumentStatus;
use App\Enums\FinancialAccountStatus;
use App\Enums\HearingStatus;
use App\Enums\HearingType;
use App\Enums\InspectionStatus;
use App\Enums\InspectionType;
use App\Enums\LeasePaymentStatus;
use App\Enums\ListEntryStatus;
use App\Enums\ListEntryType;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceRequestStatus;
use App\Enums\MaintenanceSource;
use App\Enums\MaintenanceUrgency;
use App\Enums\OfficialNotificationChannel;
use App\Enums\OfficialNotificationStatus;
use App\Enums\OfficialNotificationType;
use App\Enums\ProvisionalListStatus;
use App\Enums\RankingEntryStatus;
use App\Enums\RankingSnapshotStatus;
use App\Enums\ScoringRunStatus;
use App\Enums\TenantInvoiceStatus;
use App\Enums\TenantPaymentStatus;
use App\Enums\TenantPortalStatus;
use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TimelineEventType;
use App\Enums\TimelineEventVisibility;
use App\Enums\VisitSlotStatus;
use App\Enums\VisitStatus;
use App\Models\AdhesionRegistration;
use App\Models\AdministrativeProcess;
use App\Models\Allocation;
use App\Models\AllocationRuleSet;
use App\Models\AllocationRun;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\ApplicationScore;
use App\Models\ApplicationStatusHistory;
use App\Models\AuditLog;
use App\Models\Complaint;
use App\Models\Contest;
use App\Models\ContestHousingUnit;
use App\Models\Contract;
use App\Models\CurrentHousingSituation;
use App\Models\DataSubjectRequest;
use App\Models\DefinitiveList;
use App\Models\DefinitiveListEntry;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentAiFlag;
use App\Models\DocumentAiScore;
use App\Models\DocumentReview;
use App\Models\DocumentSubmission;
use App\Models\DocumentType;
use App\Models\Hearing;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\HousingUnit;
use App\Models\HousingVisit;
use App\Models\IncomeRecord;
use App\Models\IncomeSource;
use App\Models\KeyHandoverAppointment;
use App\Models\LeasePayment;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceRequest;
use App\Models\Municipality;
use App\Models\MunicipalTeam;
use App\Models\OfficialNotification;
use App\Models\ProcessTimelineEvent;
use App\Models\Program;
use App\Models\PropertyInspection;
use App\Models\ProvisionalList;
use App\Models\ProvisionalListEntry;
use App\Models\RankingEntry;
use App\Models\RankingSnapshot;
use App\Models\RequiredDocument;
use App\Models\ScoringRuleSet;
use App\Models\ScoringRun;
use App\Models\SensitiveDataAccessLog;
use App\Models\SupportTicket;
use App\Models\TenantFinancialAccount;
use App\Models\TenantInvoice;
use App\Models\TenantPayment;
use App\Models\TenantProfile;
use App\Models\User;
use App\Models\VisitAvailability;
use App\Models\VisitSlot;
use App\Models\WorkTask;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoAlcanenaAffordableRentSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PilotScenarioBuilder
{
    public function ensureCandidateJourney(): void
    {
        foreach ([
            ['draft', ApplicationStatus::Draft, 890.00],
            ['submitted', ApplicationStatus::Submitted, 980.00],
            ['correction', ApplicationStatus::RequiresCorrection, 1020.00],
            ['eligible', ApplicationStatus::Eligible, 1250.00],
            ['contract', ApplicationStatus::Eligible, 1340.00],
        ] as [$key, $status, $monthlyIncome]) {
            $application = $this->application((string) $key, $status, (float) $monthlyIncome);
            $this->applicationStatusHistory($application, null, $status->value);
        }
    }

    public function ensureApplicationStates(): void
    {
        $technical = $this->user('e2e.tecnico@example.test');

        $statuses = [
            'submitted' => AdministrativeProcessStatus::Received,
            'correction' => AdministrativeProcessStatus::RequiresCorrection,
            'eligible' => AdministrativeProcessStatus::AdmittedForScoring,
            'contract' => AdministrativeProcessStatus::AdmittedForScoring,
        ];

        foreach ($statuses as $key => $status) {
            $application = $this->application($key);
            $process = $this->administrativeProcess($application, $status);
            $process->forceFill([
                'assigned_to' => $technical->id,
                'assigned_at' => now()->subDays(4),
                'received_at' => now()->subDays(6),
                'preliminary_review_started_at' => now()->subDays(5),
                'document_review_started_at' => now()->subDays(4),
                'eligibility_review_started_at' => now()->subDays(3),
                'admitted_for_scoring_at' => $status === AdministrativeProcessStatus::AdmittedForScoring ? now()->subDays(2) : null,
                'summary' => 'Processo piloto fictício para validação end-to-end.',
                'internal_notes' => 'Sem dados pessoais reais. Uso exclusivo em ambiente de demonstração.',
                'created_by' => $technical->id,
                'updated_by' => $technical->id,
            ])->save();

            WorkTask::query()->updateOrCreate(
                ['task_number' => 'E2E-TASK-PROCESS-'.strtoupper((string) $key)],
                [
                    'type' => WorkTask::TYPE_ELIGIBILITY_REVIEW,
                    'source' => 'pilot_state_of_art',
                    'related_type' => $process->getMorphClass(),
                    'related_id' => $process->id,
                    'priority' => $key === 'correction' ? WorkTask::PRIORITY_HIGH : WorkTask::PRIORITY_NORMAL,
                    'status' => $key === 'eligible' ? WorkTask::STATUS_COMPLETED : WorkTask::STATUS_ASSIGNED,
                    'municipal_team_id' => $this->team('Gabinete Técnico')?->id,
                    'assigned_user_id' => $technical->id,
                    'due_at' => now()->addDays($key === 'correction' ? 1 : 5),
                    'assigned_at' => now()->subDays(4),
                    'completed_at' => $key === 'eligible' ? now()->subDay() : null,
                    'metadata' => ['scenario' => $key],
                    'created_by' => $technical->id,
                    'updated_by' => $technical->id,
                ],
            );
        }
    }

    public function ensureDocumentWorkflow(): void
    {
        $technical = $this->user('e2e.tecnico@example.test');
        $documents = [
            ['submitted', DocumentStatus::Submitted, 'Identificação civil submetida'],
            ['correction', DocumentStatus::Rejected, 'Comprovativo de domicílio fiscal rejeitado'],
            ['eligible', DocumentStatus::Validated, 'Nota de liquidação validada'],
            ['contract', DocumentStatus::Validated, 'Certidão AT validada'],
        ];

        foreach ($documents as [$key, $status, $title]) {
            $application = $this->application((string) $key);
            $submission = $this->documentSubmission($application, $status, (string) $title);

            ApplicationDocument::query()->updateOrCreate(
                [
                    'application_id' => $application->id,
                    'document_submission_id' => $submission->id,
                ],
                [
                    'document_type_id' => $submission->document_type_id,
                    'is_required' => true,
                    'status_at_submission' => $status->value,
                ],
            );

            DocumentReview::query()->updateOrCreate(
                [
                    'document_submission_id' => $submission->id,
                    'to_status' => $status->value,
                    'decision' => $status === DocumentStatus::Rejected ? 'rejected' : 'validated',
                ],
                [
                    'reviewed_by' => $technical->id,
                    'from_status' => DocumentStatus::Submitted->value,
                    'reason' => $status === DocumentStatus::Rejected ? 'Documento fictício incompleto para testar aperfeiçoamento.' : null,
                    'internal_notes' => 'Revisão documental fictícia para piloto.',
                ],
            );

            $this->documentAi($submission, $status === DocumentStatus::Rejected);
        }
    }

    public function ensureHearingAndComplaints(): void
    {
        $application = $this->application('eligible');
        $candidate = $application->user;
        $provisionalList = $this->provisionalList();
        $entry = $this->provisionalListEntry($provisionalList, $application);

        Hearing::query()->updateOrCreate(
            ['hearing_number' => 'E2E-HEARING-2026-0001'],
            [
                'provisional_list_id' => $provisionalList->id,
                'application_id' => $application->id,
                'user_id' => $candidate->id,
                'status' => HearingStatus::Open->value,
                'hearing_type' => HearingType::IntentionToChangeRanking->value,
                'subject' => 'Audiência prévia fictícia',
                'message' => 'Pronúncia fictícia para validar audiência prévia.',
                'legal_basis' => 'Regulamento municipal aplicável ao procedimento.',
                'grounds' => 'Dados fictícios sem efeitos administrativos.',
                'deadline_at' => now()->addDays(10),
                'issued_by' => $this->user('e2e.juridico@example.test')->id,
                'issued_at' => now()->subDay(),
                'candidate_visible' => true,
            ],
        );

        Complaint::query()->updateOrCreate(
            ['complaint_number' => 'E2E-COMP-2026-0001'],
            [
                'provisional_list_id' => $provisionalList->id,
                'provisional_list_entry_id' => $entry->id,
                'application_id' => $application->id,
                'user_id' => $candidate->id,
                'status' => ComplaintStatus::UnderReview->value,
                'subject' => 'Reclamação fictícia sobre pontuação',
                'grounds' => 'Fundamentos fictícios para testar revisão pelo júri.',
                'requested_outcome' => 'Revisão da classificação em ambiente de teste.',
                'submitted_at' => now()->subDay(),
                'received_at' => now()->subDay(),
                'assigned_to' => $this->user('e2e.juri@example.test')->id,
                'assigned_at' => now(),
                'candidate_visible' => true,
            ],
        );
    }

    public function ensureRankingAndAllocation(): void
    {
        $this->provisionalList();
        $definitiveList = $this->definitiveList();
        $application = $this->application('contract');
        $entry = $this->definitiveListEntry($definitiveList, $application);
        $housingUnit = $this->housingUnit('ALC-DEMO-T2-MONSANTO');
        $contestHousingUnit = $this->contestHousingUnit($housingUnit);
        $ruleSet = AllocationRuleSet::query()->where('contest_id', $this->contest()->id)->firstOrFail();
        $run = AllocationRun::query()->firstOrNew(['run_number' => 'E2E-ALLOC-RUN-2026-0001']);

        $run->forceFill([
            'allocation_rule_set_id' => $ruleSet->id,
            'program_id' => $this->program()->id,
            'contest_id' => $this->contest()->id,
            'definitive_list_id' => $definitiveList->id,
            'status' => AllocationRunStatus::Completed->value,
            'allocation_method' => AllocationMethod::Ranking->value,
            'started_by' => $this->user('e2e.habitacao@example.test')->id,
            'started_at' => now()->subDays(2),
            'completed_at' => now()->subDays(2),
            'total_housing_units' => 1,
            'total_candidates' => 2,
            'total_allocations' => 1,
            'notes' => 'Atribuição fictícia para piloto estado da arte.',
        ])->save();

        Allocation::query()->updateOrCreate(
            [
                'allocation_run_id' => $run->id,
                'application_id' => $application->id,
            ],
            [
                'allocation_rule_set_id' => $ruleSet->id,
                'program_id' => $this->program()->id,
                'contest_id' => $this->contest()->id,
                'definitive_list_id' => $definitiveList->id,
                'definitive_list_entry_id' => $entry->id,
                'user_id' => $application->user_id,
                'contest_housing_unit_id' => $contestHousingUnit->id,
                'housing_unit_id' => $housingUnit->id,
                'allocation_method' => AllocationMethod::Ranking->value,
                'status' => AllocationStatus::ReadyForContract->value,
                'rank_position' => 1,
                'preference_order' => 1,
                'allocated_by' => $this->user('e2e.habitacao@example.test')->id,
                'allocated_at' => now()->subDays(2),
                'offered_at' => now()->subDays(2),
                'accepted_at' => now()->subDay(),
                'ready_for_contract_at' => now()->subDay(),
            ],
        );
    }

    public function ensureContractsAndTenant(): void
    {
        $application = $this->application('contract');
        $allocation = Allocation::query()->where('application_id', $application->id)->firstOrFail();
        $housingUnit = $this->housingUnit('ALC-DEMO-T2-MONSANTO');
        $housingManager = $this->user('e2e.habitacao@example.test');

        $contract = Contract::query()->firstOrNew(['contract_number' => 'E2E-CON-2026-0001']);
        $contract->forceFill([
            'housing_unit_id' => $housingUnit->id,
            'program_id' => $this->program()->id,
            'contest_id' => $this->contest()->id,
            'application_id' => $application->id,
            'allocation_id' => $allocation->id,
            'user_id' => $application->user_id,
            'household_id' => $application->household_id,
            'contest_housing_unit_id' => $allocation->contest_housing_unit_id,
            'start_date' => CarbonImmutable::create(2026, 9, 1),
            'end_date' => CarbonImmutable::create(2031, 8, 31),
            'duration_months' => 60,
            'renewal_allowed' => true,
            'monthly_rent' => 285.00,
            'deposit_amount' => 285.00,
            'payment_day' => 8,
            'payment_method_description' => 'Gestão administrativa/manual no âmbito aceite.',
            'issued_at' => now()->subDay(),
            'issued_by' => $housingManager->id,
            'signed_at' => now(),
            'signed_by' => $housingManager->id,
            'activated_at' => now(),
            'activated_by' => $housingManager->id,
            'created_by' => $housingManager->id,
            'updated_by' => $housingManager->id,
            'status' => ContractStatus::Active->value,
            'tenant_name' => 'Candidato Fictício Contrato',
            'tenant_email' => 'e2e.candidato.contract@example.test',
            'landlord_name' => 'Município de Alcanena',
            'housing_address' => 'Morada municipal fictícia para demonstração',
            'housing_typology' => 'T2',
            'housing_area' => 82.00,
        ])->save();

        TenantProfile::query()->updateOrCreate(
            ['user_id' => $application->user_id],
            [
                'status' => TenantPortalStatus::Active->value,
                'activated_at' => now(),
                'activation_notes' => 'Ativação fictícia por contrato piloto.',
                'created_by' => $housingManager->id,
                'updated_by' => $housingManager->id,
            ],
        );

        $account = TenantFinancialAccount::query()->firstOrNew(['account_number' => 'E2E-FIN-2026-0001']);
        $account->forceFill([
            'lease_contract_id' => $contract->id,
            'application_id' => $application->id,
            'allocation_id' => $allocation->id,
            'user_id' => $application->user_id,
            'household_id' => $application->household_id,
            'housing_unit_id' => $housingUnit->id,
            'status' => FinancialAccountStatus::Active->value,
            'currency' => 'EUR',
            'opened_at' => now(),
            'current_balance' => 285.00,
            'total_issued' => 570.00,
            'total_paid' => 285.00,
            'total_overdue' => 285.00,
            'next_due_date' => now()->addMonth()->startOfMonth()->addDays(7),
            'created_by' => $housingManager->id,
            'updated_by' => $housingManager->id,
        ])->save();

        LeasePayment::query()->updateOrCreate(
            ['payment_number' => 'E2E-PAY-2026-0001'],
            [
                'tenant_financial_account_id' => $account->id,
                'lease_contract_id' => $contract->id,
                'user_id' => $application->user_id,
                'status' => LeasePaymentStatus::Confirmed->value,
                'amount' => 285.00,
                'allocated_amount' => 285.00,
                'unallocated_amount' => 0,
                'currency' => 'EUR',
                'payment_date' => now()->subMonth()->toDateString(),
                'value_date' => now()->subMonth()->toDateString(),
                'received_at' => now()->subMonth(),
                'confirmed_at' => now()->subMonth(),
                'method' => 'manual',
                'source' => 'municipal_backoffice',
                'payer_name' => 'Candidato Fictício Contrato',
                'created_by' => $this->user('e2e.financeiro@example.test')->id,
                'confirmed_by' => $this->user('e2e.financeiro@example.test')->id,
            ],
        );

        TenantInvoice::query()->updateOrCreate(
            ['invoice_number' => 'E2E-INV-2026-0001'],
            [
                'tenant_financial_account_id' => $account->id,
                'lease_contract_id' => $contract->id,
                'user_id' => $application->user_id,
                'housing_unit_id' => $housingUnit->id,
                'status' => TenantInvoiceStatus::Overdue->value,
                'charge_type' => 'rent',
                'period_year' => 2026,
                'period_month' => 10,
                'issue_date' => CarbonImmutable::create(2026, 10, 1),
                'due_date' => CarbonImmutable::create(2026, 10, 8),
                'original_amount' => 285.00,
                'amount_due' => 285.00,
                'amount_paid' => 0,
                'amount_outstanding' => 285.00,
                'currency' => 'EUR',
                'issued_at' => CarbonImmutable::create(2026, 10, 1),
                'created_by' => $this->user('e2e.financeiro@example.test')->id,
            ],
        );

        TenantPayment::query()->updateOrCreate(
            ['payment_number' => 'E2E-TENPAY-2026-0001'],
            [
                'tenant_financial_account_id' => $account->id,
                'lease_contract_id' => $contract->id,
                'user_id' => $application->user_id,
                'status' => TenantPaymentStatus::Confirmed->value,
                'amount' => 285.00,
                'allocated_amount' => 285.00,
                'unallocated_amount' => 0,
                'currency' => 'EUR',
                'payment_date' => now()->subMonth()->toDateString(),
                'registered_at' => now()->subMonth(),
                'confirmed_at' => now()->subMonth(),
                'method' => 'manual',
                'source' => 'municipal_backoffice',
                'payer_name' => 'Candidato Fictício Contrato',
                'registered_by' => $this->user('e2e.financeiro@example.test')->id,
                'confirmed_by' => $this->user('e2e.financeiro@example.test')->id,
            ],
        );

        KeyHandoverAppointment::query()->updateOrCreate(
            [
                'application_id' => $application->id,
                'user_id' => $application->user_id,
                'housing_unit_id' => $housingUnit->id,
            ],
            [
                'allocation_id' => $allocation->id,
                'contest_id' => $this->contest()->id,
                'contest_housing_unit_id' => $allocation->contest_housing_unit_id,
                'status' => 'scheduled',
                'scheduled_for' => now()->addDays(7),
                'location' => 'Paços do Concelho - atendimento municipal',
                'instructions' => 'Entrega de chaves fictícia para validação de agenda.',
                'metadata' => ['pilot' => true],
            ],
        );
    }

    public function ensureMaintenanceAndInspections(): void
    {
        $contract = Contract::query()->where('contract_number', 'E2E-CON-2026-0001')->firstOrFail();
        $housingUnit = $this->housingUnit('ALC-DEMO-T2-MONSANTO');
        $category = MaintenanceCategory::query()->first();
        $maintenance = MaintenanceRequest::query()->firstOrNew(['request_number' => 'E2E-MAN-2026-0001']);

        $maintenance->forceFill([
            'housing_unit_id' => $housingUnit->id,
            'lease_contract_id' => $contract->id,
            'application_id' => $contract->application_id,
            'user_id' => $contract->user_id,
            'maintenance_category_id' => $category?->id,
            'source' => MaintenanceSource::Tenant->value,
            'title' => 'Pedido urgente fictício de manutenção',
            'description' => 'Pedido fictício para validar triagem, SLA e workload.',
            'location_in_property' => 'Cozinha',
            'tenant_availability' => 'Dias úteis de manhã',
            'access_instructions' => 'Contacto pela área do inquilino.',
            'priority' => MaintenancePriority::Urgent->value,
            'urgency' => MaintenanceUrgency::Urgent->value,
            'technical_priority' => MaintenanceUrgency::Urgent->value,
            'status' => MaintenanceRequestStatus::Scheduled->value,
            'reported_at' => now()->subDays(2),
            'scheduled_for' => now()->addDay(),
            'created_by' => $contract->user_id,
            'updated_by' => $this->user('e2e.manutencao@example.test')->id,
        ])->save();

        PropertyInspection::query()->updateOrCreate(
            ['inspection_number' => 'E2E-INSP-2026-0001'],
            [
                'housing_unit_id' => $housingUnit->id,
                'lease_contract_id' => $contract->id,
                'application_id' => $contract->application_id,
                'inspection_type' => InspectionType::Periodic->value,
                'status' => InspectionStatus::Scheduled->value,
                'inspector_user_id' => $this->user('e2e.vistorias@example.test')->id,
                'scheduled_for' => now()->addDays(10),
                'summary' => 'Vistoria preventiva fictícia.',
                'tenant_visible' => true,
                'tenant_present' => true,
                'created_by' => $this->user('e2e.vistorias@example.test')->id,
            ],
        );
    }

    public function ensureVisitsAndSupport(): void
    {
        $contest = $this->contest();
        $housingUnit = $this->housingUnit('ALC-DEMO-T2-MONSANTO');
        $staff = $this->user('e2e.atendimento@example.test');
        $candidate = $this->application('submitted')->user;
        $start = now()->addDays(14)->setTime(10, 0);

        $availability = VisitAvailability::query()->firstOrNew(['title' => 'Open house piloto - Monsanto T2']);
        $availability->forceFill([
            'contest_id' => $contest->id,
            'housing_unit_id' => $housingUnit->id,
            'staff_user_id' => $staff->id,
            'description' => 'Visita aberta fictícia para validação de agenda municipal.',
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHours(2),
            'slot_duration_minutes' => 30,
            'capacity_per_slot' => 4,
            'buffer_minutes' => 0,
            'timezone' => config('app.timezone', 'Europe/Lisbon'),
            'is_active' => true,
            'created_by' => $staff->id,
            'updated_by' => $staff->id,
        ])->save();

        $slot = VisitSlot::query()
            ->where('visit_availability_id', $availability->id)
            ->where('starts_at', $start)
            ->firstOrNew();
        $slot->forceFill([
            'visit_availability_id' => $availability->id,
            'contest_id' => $contest->id,
            'housing_unit_id' => $housingUnit->id,
            'staff_user_id' => $staff->id,
            'starts_at' => $start,
            'ends_at' => $start->copy()->addMinutes(30),
            'status' => VisitSlotStatus::Available->value,
            'capacity' => 4,
            'booked_count' => 1,
            'location' => 'Habitação piloto Monsanto',
            'meeting_point' => 'Entrada principal do edifício',
            'notes' => 'Horário fictício de open house.',
        ])->save();

        HousingVisit::query()->updateOrCreate(
            ['visit_number' => 'E2E-VISIT-2026-0001'],
            [
                'visit_slot_id' => $slot->id,
                'application_id' => $this->application('submitted')->id,
                'contest_id' => $contest->id,
                'housing_unit_id' => $housingUnit->id,
                'candidate_user_id' => $candidate->id,
                'staff_user_id' => $staff->id,
                'status' => VisitStatus::Confirmed->value,
                'scheduled_at' => now(),
                'starts_at' => $slot->starts_at,
                'ends_at' => $slot->ends_at,
                'confirmed_at' => now(),
                'candidate_notes' => 'Visita piloto marcada pelo candidato.',
                'location' => $slot->location,
                'meeting_point' => $slot->meeting_point,
            ],
        );

        SupportTicket::query()->updateOrCreate(
            ['ticket_number' => 'E2E-SUP-2026-0001'],
            [
                'user_id' => $candidate->id,
                'application_id' => $this->application('submitted')->id,
                'contest_id' => $contest->id,
                'housing_unit_id' => $housingUnit->id,
                'assigned_to' => $staff->id,
                'category' => TicketCategory::Visits->value,
                'priority' => TicketPriority::High->value,
                'status' => TicketStatus::InProgress->value,
                'subject' => 'Dúvida fictícia sobre visita aberta',
                'description' => 'Ticket fictício para validar apoio e interações.',
                'context' => ['pilot' => true],
                'last_message_at' => now(),
            ],
        );
    }

    public function ensureOperationsAgenda(): void
    {
        $admin = $this->user('e2e.admin@example.test');
        $application = $this->application('eligible');

        foreach ([
            ['E2E-TASK-DOC-2026-0001', WorkTask::TYPE_DOCUMENT_REVIEW, WorkTask::STATUS_OVERDUE, WorkTask::PRIORITY_URGENT, -1, 'e2e.tecnico@example.test'],
            ['E2E-TASK-SCORE-2026-0001', WorkTask::TYPE_SCORING_REVIEW, WorkTask::STATUS_ASSIGNED, WorkTask::PRIORITY_HIGH, 2, 'e2e.juri@example.test'],
            ['E2E-TASK-RGPD-2026-0001', WorkTask::TYPE_RGPD_REQUEST, WorkTask::STATUS_PENDING, WorkTask::PRIORITY_NORMAL, 5, 'e2e.auditor@example.test'],
        ] as [$number, $type, $status, $priority, $dueOffset, $email]) {
            WorkTask::query()->updateOrCreate(
                ['task_number' => (string) $number],
                [
                    'type' => (string) $type,
                    'source' => 'pilot_state_of_art',
                    'related_type' => $application->getMorphClass(),
                    'related_id' => $application->id,
                    'priority' => (string) $priority,
                    'status' => (string) $status,
                    'municipal_team_id' => $this->teamForUser((string) $email)?->id,
                    'assigned_user_id' => $this->user((string) $email)->id,
                    'due_at' => now()->addDays((int) $dueOffset),
                    'assigned_at' => now()->subDay(),
                    'metadata' => ['pilot' => true],
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                ],
            );
        }

        ProcessTimelineEvent::query()->updateOrCreate(
            ['event_number' => 'E2E-TL-2026-0001'],
            [
                'user_id' => $application->user_id,
                'application_id' => $application->id,
                'adhesion_registration_id' => $application->adhesion_registration_id,
                'contest_id' => $application->contest_id,
                'event_type' => TimelineEventType::SystemEvent->value,
                'visibility' => TimelineEventVisibility::BackofficeOnly->value,
                'public_status' => null,
                'title' => 'Candidatura preparada para classificação',
                'description' => 'Evento fictício para validar cronologia, case workspace e dashboard.',
                'occurred_at' => now()->subDay(),
                'created_by' => $admin->id,
                'metadata' => ['pilot' => true],
            ],
        );

        OfficialNotification::query()->updateOrCreate(
            ['notification_number' => 'E2E-NOT-2026-0001'],
            [
                'user_id' => $application->user_id,
                'recipient_email' => $application->user->email,
                'application_id' => $application->id,
                'notification_type' => OfficialNotificationType::VisitConfirmed->value,
                'event_code' => 'pilot.application.ready',
                'status' => OfficialNotificationStatus::Sent->value,
                'channel' => OfficialNotificationChannel::CandidateArea->value,
                'priority' => 'normal',
                'subject' => 'Notificação fictícia de candidatura',
                'title' => 'Candidatura em análise',
                'body' => 'Mensagem fictícia para validação da área do candidato.',
                'requires_acknowledgement' => false,
                'sent_at' => now()->subDay(),
                'created_by' => $admin->id,
            ],
        );
    }

    public function ensureRgpdAndAudit(): void
    {
        $auditor = $this->user('e2e.auditor@example.test');
        $candidate = $this->application('submitted')->user;

        DataSubjectRequest::query()->updateOrCreate(
            ['request_number' => 'E2E-RGPD-2026-0001'],
            [
                'user_id' => $candidate->id,
                'requester_name' => 'Candidato Fictício RGPD',
                'requester_email' => $candidate->email,
                'request_type' => DataSubjectRequestType::Access->value,
                'status' => DataSubjectRequestStatus::UnderReview->value,
                'description' => 'Pedido RGPD fictício para validação operacional.',
                'identity_verified_at' => now(),
                'received_at' => now()->subDay(),
                'due_at' => now()->addDays(20),
                'assigned_to' => $auditor->id,
                'created_by' => $auditor->id,
                'internal_notes' => 'Sem dados pessoais reais.',
            ],
        );

        SensitiveDataAccessLog::query()->updateOrCreate(
            [
                'user_id' => $auditor->id,
                'subject_user_id' => $candidate->id,
                'resource_type' => 'application',
                'resource_id' => $this->application('submitted')->id,
                'action' => 'view',
            ],
            [
                'sensitivity_level' => 'controlled',
                'access_reason' => 'Validação fictícia RGPD para piloto.',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'MV HAB Seeder',
                'accessed_at' => now(),
            ],
        );

        AuditLog::query()->updateOrCreate(
            ['event' => 'pilot_state_of_art.seeded'],
            [
                'user_id' => $auditor->id,
                'module' => 'seeders',
                'action' => 'state_of_art_seed',
                'description' => 'Seeder piloto estado da arte executado com dados fictícios.',
                'metadata' => ['safe_demo_data' => true],
                'ip_address' => '127.0.0.1',
                'user_agent' => 'MV HAB Seeder',
                'occurred_at' => now(),
            ],
        );
    }

    public function application(string $key, ?ApplicationStatus $status = null, float $monthlyIncome = 1200.00): Application
    {
        $candidate = $this->candidate($key);
        $registration = $this->adhesionRegistration($candidate, $monthlyIncome);
        $household = $this->household($registration, $monthlyIncome);
        $currentHousing = $this->currentHousingSituation($registration);
        $applicationNumber = 'E2E-APP-'.strtoupper($key).'-2026';
        $application = Application::query()->firstOrNew(['application_number' => $applicationNumber]);

        if ($status === null && $application->exists) {
            $status = ApplicationStatus::tryFrom((string) $application->getRawOriginal('status'));
        }

        $status ??= ApplicationStatus::Submitted;

        $application->forceFill([
            'public_id' => $application->public_id ?: (string) Str::uuid(),
            'user_id' => $candidate->id,
            'adhesion_registration_id' => $registration->id,
            'program_id' => $this->program()->id,
            'contest_id' => $this->contest()->id,
            'household_id' => $household->id,
            'current_housing_situation_id' => $currentHousing->id,
            'status' => $status->value,
            'submitted_at' => $status === ApplicationStatus::Draft ? null : now()->subDays(7),
            'locked_at' => $status === ApplicationStatus::Draft ? null : now()->subDays(7),
            'declaration_accepted' => $status !== ApplicationStatus::Draft,
            'declaration_accepted_at' => $status === ApplicationStatus::Draft ? null : now()->subDays(7),
            'contest_rules_accepted' => $status !== ApplicationStatus::Draft,
            'contest_rules_accepted_at' => $status === ApplicationStatus::Draft ? null : now()->subDays(7),
            'data_processing_accepted' => $status !== ApplicationStatus::Draft,
            'data_processing_accepted_at' => $status === ApplicationStatus::Draft ? null : now()->subDays(7),
            'truthfulness_accepted' => $status !== ApplicationStatus::Draft,
            'truthfulness_accepted_at' => $status === ApplicationStatus::Draft ? null : now()->subDays(7),
            'data_current_confirmed' => $status !== ApplicationStatus::Draft,
            'data_current_confirmed_at' => $status === ApplicationStatus::Draft ? null : now()->subDays(7),
            'candidate_notes' => 'Candidatura fictícia '.$key.' para piloto estado da arte.',
            'created_by' => $candidate->id,
            'updated_by' => $candidate->id,
        ])->save();

        return $application->refresh();
    }

    private function candidate(string $key): User
    {
        $email = 'e2e.candidato.'.$key.'@example.test';
        $user = User::query()->firstOrNew(['email' => $email]);
        $municipality = $this->municipality();

        $user->forceFill([
            'municipality_id' => $municipality->id,
            'name' => 'E2E Candidato '.str($key)->replace('-', ' ')->title(),
            'email_verified_at' => CarbonImmutable::create(2026, 1, 1),
            'status' => 'active',
            'mfa_required' => false,
            'internal_notes' => 'Candidato fictício para piloto estado da arte.',
        ]);

        if (! $user->exists) {
            $user->forceFill(['password' => Hash::make(Str::random(64))]);
        }

        $user->save();
        $user->assignRole('candidate');

        return $user;
    }

    private function adhesionRegistration(User $candidate, float $monthlyIncome): AdhesionRegistration
    {
        $registration = AdhesionRegistration::query()->firstOrNew(['user_id' => $candidate->id]);

        $registration->forceFill([
            'status' => AdhesionRegistrationStatus::Registered->value,
            'full_name' => $candidate->name,
            'email' => $candidate->email,
            'phone' => null,
            'mobile_phone' => null,
            'document_type' => 'citizen_card',
            'document_number' => null,
            'nif' => null,
            'birth_date' => CarbonImmutable::create(1990, 5, 15),
            'nationality' => 'Portuguesa',
            'address' => 'Morada fictícia reservada',
            'postal_code' => '0000-000',
            'city' => 'Alcanena',
            'parish' => 'Freguesia fictícia',
            'municipality' => 'Alcanena',
            'accepts_terms' => true,
            'accepts_data_processing' => true,
            'accepted_terms_at' => now()->subDays(12),
            'accepted_data_processing_at' => now()->subDays(12),
            'submitted_at' => now()->subDays(12),
        ])->save();

        return $registration;
    }

    private function household(AdhesionRegistration $registration, float $monthlyIncome): Household
    {
        $household = Household::query()->firstOrNew(['adhesion_registration_id' => $registration->id]);
        $household->forceFill([
            'name' => 'Agregado fictício '.$registration->id,
            'household_type' => 'single',
            'monthly_income' => $monthlyIncome,
            'members_count' => 1,
            'notes' => 'Agregado fictício para demonstração.',
        ])->save();

        $member = HouseholdMember::query()
            ->where('household_id', $household->id)
            ->where('is_applicant', true)
            ->firstOrNew();
        $member->forceFill([
            'household_id' => $household->id,
            'adhesion_registration_id' => $registration->id,
            'is_applicant' => true,
            'full_name' => $registration->full_name,
            'birth_date' => CarbonImmutable::create(1990, 5, 15),
            'relationship' => 'applicant',
            'nationality' => 'Portuguesa',
            'is_dependent' => false,
            'monthly_declared_income' => $monthlyIncome,
            'annual_declared_income' => $monthlyIncome * 14,
            'has_no_income' => false,
            'is_exempt_from_irs' => false,
        ])->save();

        $source = $this->incomeSource();
        IncomeRecord::query()->updateOrCreate(
            [
                'household_member_id' => $member->id,
                'household_id' => $household->id,
                'adhesion_registration_id' => $registration->id,
                'income_source_id' => $source->id,
                'reference_year' => 2026,
            ],
            [
                'description' => 'Rendimento fictício para teste municipal.',
                'monthly_amount' => $monthlyIncome,
                'annual_amount' => $monthlyIncome * 14,
                'is_current' => true,
                'is_taxable' => true,
            ],
        );

        return $household;
    }

    private function currentHousingSituation(AdhesionRegistration $registration): CurrentHousingSituation
    {
        return CurrentHousingSituation::query()->updateOrCreate(
            ['adhesion_registration_id' => $registration->id],
            [
                'housing_status' => 'rented',
                'current_address' => 'Morada fictícia reservada',
                'current_postal_code' => '0000-000',
                'current_city' => 'Alcanena',
                'current_parish' => 'Freguesia fictícia',
                'current_municipality' => 'Alcanena',
                'resides_in_municipality' => true,
                'residence_years_in_municipality' => 4,
                'current_housing_typology' => 'T1',
                'current_housing_rooms' => 2,
                'current_monthly_rent' => 420.00,
                'is_overcrowded' => false,
                'is_at_risk_of_eviction' => false,
                'request_reason' => 'Dados fictícios para piloto.',
            ],
        );
    }

    private function administrativeProcess(Application $application, AdministrativeProcessStatus $status): AdministrativeProcess
    {
        $process = AdministrativeProcess::query()->firstOrNew([
            'process_number' => 'E2E-PROC-'.$application->id.'-2026',
        ]);

        $process->forceFill([
            'application_id' => $application->id,
            'program_id' => $application->program_id,
            'contest_id' => $application->contest_id,
            'user_id' => $application->user_id,
            'status' => $status->value,
        ])->save();

        return $process;
    }

    private function documentSubmission(Application $application, DocumentStatus $status, string $title): DocumentSubmission
    {
        $documentType = $this->documentType();
        $requiredDocument = $this->requiredDocument($documentType);
        $submission = DocumentSubmission::query()
            ->where('application_id', $application->id)
            ->where('title', $title)
            ->firstOrNew();

        $submission->forceFill([
            'document_type_id' => $documentType->id,
            'required_document_id' => $requiredDocument->id,
            'user_id' => $application->user_id,
            'adhesion_registration_id' => $application->adhesion_registration_id,
            'household_id' => $application->household_id,
            'application_id' => $application->id,
            'status' => $status->value,
            'title' => $title,
            'original_filename' => 'documento-ficticio-'.$application->id.'.pdf',
            'stored_filename' => 'documento-ficticio-'.$application->id.'.pdf',
            'storage_disk' => 'local',
            'storage_path' => 'demo/placeholders/documento-ficticio-'.$application->id.'.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'checksum' => hash('sha256', 'pilot-'.$application->id.'-'.$title),
            'submitted_at' => now()->subDays(6),
            'submitted_by' => $application->user_id,
            'reviewed_at' => $status === DocumentStatus::Submitted ? null : now()->subDays(2),
            'reviewed_by' => $status === DocumentStatus::Submitted ? null : $this->user('e2e.tecnico@example.test')->id,
            'validated_at' => $status === DocumentStatus::Validated ? now()->subDays(2) : null,
            'validated_by' => $status === DocumentStatus::Validated ? $this->user('e2e.tecnico@example.test')->id : null,
            'rejected_at' => $status === DocumentStatus::Rejected ? now()->subDays(2) : null,
            'rejected_by' => $status === DocumentStatus::Rejected ? $this->user('e2e.tecnico@example.test')->id : null,
            'rejection_reason' => $status === DocumentStatus::Rejected ? 'Documento fictício incompleto.' : null,
        ])->save();

        return $submission;
    }

    private function documentAi(DocumentSubmission $submission, bool $manualReview): void
    {
        $analysis = DocumentAiAnalysis::query()
            ->where('document_submission_id', $submission->id)
            ->firstOrNew();

        $analysis->forceFill([
            'document_submission_id' => $submission->id,
            'status' => $manualReview ? DocumentAiStatus::ManualReview->value : DocumentAiStatus::Completed->value,
            'engine' => 'local-demo',
            'model' => 'ocr-pilot',
            'source_disk' => 'local',
            'source_path' => $submission->storage_path,
            'source_mime' => $submission->mime_type,
            'source_size_bytes' => $submission->file_size,
            'source_sha256' => $submission->checksum,
            'raw_text' => 'Texto OCR fictício sem dados pessoais reais.',
            'summary' => $manualReview ? 'Requer revisão manual.' : 'Documento legível e compatível.',
            'confidence' => $manualReview ? 62.00 : 91.00,
            'started_at' => now()->subDays(2),
            'completed_at' => now()->subDays(2),
            'ocr_status' => DocumentAiOcrStatus::Completed->value,
            'ocr_available' => true,
            'ocr_engine' => 'tesseract-local',
            'ocr_language' => 'por+eng',
            'ocr_text' => 'Texto OCR fictício sem NIF, morada ou documento real.',
            'ocr_quality_score' => $manualReview ? 62.00 : 91.00,
            'ocr_pages_count' => 1,
            'ocr_processed_at' => now()->subDays(2),
            'classification_status' => $manualReview ? DocumentAiClassificationStatus::ManualReview->value : DocumentAiClassificationStatus::Completed->value,
            'detected_document_type' => null,
            'detected_document_label' => $manualReview ? 'Documento requer revisão' : 'Documento compatível',
            'classification_confidence' => $manualReview ? 58.00 : 90.00,
            'classification_source' => 'local_demo',
            'classification_requires_manual_review' => $manualReview,
            'classified_at' => now()->subDays(2),
            'created_by' => $this->user('e2e.tecnico@example.test')->id,
            'updated_by' => $this->user('e2e.tecnico@example.test')->id,
        ])->save();

        DocumentAiScore::query()->updateOrCreate(
            ['document_ai_analysis_id' => $analysis->id],
            [
                'document_submission_id' => $submission->id,
                'application_id' => $submission->application_id,
                'score' => $manualReview ? 62 : 91,
                'label' => $manualReview ? DocumentAiScoreLabel::RequerRevisao->value : DocumentAiScoreLabel::MuitoConfiavel->value,
                'components' => ['ocr' => $manualReview ? 62 : 91],
                'explanation' => 'Score fictício de IA documental.',
                'summary' => $manualReview ? 'Revisão manual recomendada.' : 'Sem alertas relevantes.',
                'requires_manual_review' => $manualReview,
                'calculated_at' => now()->subDays(2),
            ],
        );

        if ($manualReview) {
            DocumentAiFlag::query()->updateOrCreate(
                [
                    'document_ai_analysis_id' => $analysis->id,
                    'code' => DocumentAiRiskFlagCode::InsufficientOcr->value,
                ],
                [
                    'severity' => DocumentAiRiskSeverity::Medium->value,
                    'message' => 'Baixa confiança em documento fictício.',
                    'score_impact' => -10,
                    'detected_by' => 'local_demo',
                    'confidence' => 58.00,
                    'details' => ['pilot' => true],
                    'requires_manual_review' => true,
                ],
            );
        }
    }

    private function provisionalList(): ProvisionalList
    {
        $snapshot = $this->rankingSnapshot();
        $list = ProvisionalList::query()->firstOrNew(['list_number' => 'E2E-LP-2026-0001']);

        $list->forceFill([
            'program_id' => $this->program()->id,
            'contest_id' => $this->contest()->id,
            'ranking_snapshot_id' => $snapshot->id,
            'scoring_run_id' => $snapshot->scoring_run_id,
            'title' => 'Lista provisória piloto estado da arte',
            'description' => 'Lista fictícia para validação de audiência e reclamações.',
            'status' => ProvisionalListStatus::ComplaintPeriodOpen->value,
            'version_number' => 1,
            'generated_by' => $this->user('e2e.juri@example.test')->id,
            'generated_at' => now()->subDays(3),
            'approved_by' => $this->user('e2e.juri@example.test')->id,
            'approved_at' => now()->subDays(2),
            'published_by' => $this->user('e2e.juri@example.test')->id,
            'published_at' => now()->subDays(2),
            'complaint_period_starts_at' => now()->subDays(2),
            'complaint_period_ends_at' => now()->addDays(8),
            'anonymization_mode' => 'public_identifier_only',
            'public_visibility' => true,
            'legal_basis' => 'Regulamento municipal aplicável.',
        ])->save();

        $this->provisionalListEntry($list, $this->application('eligible'));
        $this->provisionalListEntry($list, $this->application('contract'));

        return $list;
    }

    private function definitiveList(): DefinitiveList
    {
        $provisional = $this->provisionalList();
        $list = DefinitiveList::query()->firstOrNew(['list_number' => 'E2E-LD-2026-0001']);

        $list->forceFill([
            'program_id' => $this->program()->id,
            'contest_id' => $this->contest()->id,
            'provisional_list_id' => $provisional->id,
            'ranking_snapshot_id' => $provisional->ranking_snapshot_id,
            'scoring_run_id' => $provisional->scoring_run_id,
            'title' => 'Lista definitiva piloto estado da arte',
            'description' => 'Lista definitiva fictícia para validação de atribuição.',
            'status' => DefinitiveListStatus::Published->value,
            'version_number' => 1,
            'generated_by' => $this->user('e2e.juri@example.test')->id,
            'generated_at' => now()->subDay(),
            'approved_by' => $this->user('e2e.juri@example.test')->id,
            'approved_at' => now()->subDay(),
            'published_by' => $this->user('e2e.juri@example.test')->id,
            'published_at' => now()->subDay(),
            'anonymization_mode' => 'public_identifier_only',
            'public_visibility' => true,
            'legal_basis' => 'Regulamento municipal aplicável.',
        ])->save();

        $this->definitiveListEntry($list, $this->application('contract'));

        return $list;
    }

    private function rankingSnapshot(): RankingSnapshot
    {
        $run = $this->scoringRun();
        $snapshot = RankingSnapshot::query()->firstOrNew([
            'scoring_run_id' => $run->id,
            'snapshot_number' => 99,
        ]);

        $snapshot->forceFill([
            'program_id' => $this->program()->id,
            'contest_id' => $this->contest()->id,
            'status' => RankingSnapshotStatus::Internal->value,
            'generated_by' => $this->user('e2e.juri@example.test')->id,
            'generated_at' => now()->subDays(3),
            'notes' => 'Snapshot interno fictício para validar ranking e listas.',
        ])->save();

        $this->rankingEntry($snapshot, $this->applicationScore($this->application('eligible'), 82.5, 2));
        $this->rankingEntry($snapshot, $this->applicationScore($this->application('contract'), 91.0, 1));

        return $snapshot;
    }

    private function scoringRun(): ScoringRun
    {
        $ruleSet = ScoringRuleSet::query()->where('contest_id', $this->contest()->id)->firstOrFail();
        $run = ScoringRun::query()
            ->where('scoring_rule_set_id', $ruleSet->id)
            ->where('notes', 'Execução piloto estado da arte.')
            ->firstOrNew();

        $run->forceFill([
            'scoring_rule_set_id' => $ruleSet->id,
            'program_id' => $this->program()->id,
            'contest_id' => $this->contest()->id,
            'status' => ScoringRunStatus::Completed->value,
            'started_by' => $this->user('e2e.juri@example.test')->id,
            'started_at' => now()->subDays(3),
            'completed_at' => now()->subDays(3),
            'total_applications' => 2,
            'scored_applications' => 2,
            'manual_review_applications' => 0,
            'excluded_applications' => 0,
            'notes' => 'Execução piloto estado da arte.',
        ])->save();

        return $run;
    }

    private function applicationScore(Application $application, float $score, int $rank): ApplicationScore
    {
        $run = $this->scoringRun();
        $scoreModel = ApplicationScore::query()->firstOrNew([
            'scoring_run_id' => $run->id,
            'application_id' => $application->id,
        ]);

        $scoreModel->forceFill([
            'scoring_rule_set_id' => $run->scoring_rule_set_id,
            'program_id' => $application->program_id,
            'contest_id' => $application->contest_id,
            'user_id' => $application->user_id,
            'status' => ApplicationScoreStatus::Calculated->value,
            'total_score' => $score,
            'automatic_score' => $score,
            'manual_score' => 0,
            'rank_position' => $rank,
            'is_tied' => false,
            'requires_manual_review' => false,
            'excluded_from_ranking' => false,
            'calculated_at' => now()->subDays(3),
            'calculated_by' => $this->user('e2e.juri@example.test')->id,
        ])->save();

        return $scoreModel;
    }

    private function rankingEntry(RankingSnapshot $snapshot, ApplicationScore $score): RankingEntry
    {
        return RankingEntry::query()->updateOrCreate(
            [
                'ranking_snapshot_id' => $snapshot->id,
                'application_id' => $score->application_id,
            ],
            [
                'application_score_id' => $score->id,
                'rank_position' => $score->rank_position,
                'total_score' => $score->total_score,
                'is_tied' => false,
                'status' => RankingEntryStatus::Ranked->value,
            ],
        );
    }

    private function provisionalListEntry(ProvisionalList $list, Application $application): ProvisionalListEntry
    {
        $score = ApplicationScore::query()->where('application_id', $application->id)->latest('id')->first();
        $rankingEntry = RankingEntry::query()->where('application_id', $application->id)->latest('id')->first();
        $rankPosition = $score === null ? 1 : $score->rank_position;
        $totalScore = $score === null ? 80 : $score->total_score;

        return ProvisionalListEntry::query()->updateOrCreate(
            [
                'provisional_list_id' => $list->id,
                'application_id' => $application->id,
            ],
            [
                'application_score_id' => $score?->id,
                'ranking_entry_id' => $rankingEntry?->id,
                'user_id' => $application->user_id,
                'entry_type' => ListEntryType::Ranked->value,
                'status' => ListEntryStatus::Ranked->value,
                'rank_position' => $rankPosition,
                'total_score' => $totalScore,
                'public_identifier' => 'E2E-PUB-'.$application->id,
                'candidate_name_masked' => 'Candidato '.$application->id,
                'application_number_masked' => 'E2E-APP-****-'.$application->id,
                'decision_summary' => 'Entrada fictícia para lista provisória.',
                'metadata' => ['pilot' => true],
            ],
        );
    }

    private function definitiveListEntry(DefinitiveList $list, Application $application): DefinitiveListEntry
    {
        $provisional = ProvisionalListEntry::query()->where('application_id', $application->id)->latest('id')->first();
        $score = ApplicationScore::query()->where('application_id', $application->id)->latest('id')->first();
        $rankingEntry = RankingEntry::query()->where('application_id', $application->id)->latest('id')->first();
        $rankPosition = $score === null ? 1 : $score->rank_position;
        $totalScore = $score === null ? 90 : $score->total_score;

        return DefinitiveListEntry::query()->updateOrCreate(
            [
                'definitive_list_id' => $list->id,
                'application_id' => $application->id,
            ],
            [
                'provisional_list_entry_id' => $provisional?->id,
                'application_score_id' => $score?->id,
                'ranking_entry_id' => $rankingEntry?->id,
                'user_id' => $application->user_id,
                'entry_type' => ListEntryType::Ranked->value,
                'status' => ListEntryStatus::Ranked->value,
                'rank_position' => $rankPosition,
                'previous_rank_position' => $rankPosition,
                'total_score' => $totalScore,
                'previous_total_score' => $totalScore,
                'public_identifier' => 'E2E-DEF-'.$application->id,
                'candidate_name_masked' => 'Candidato '.$application->id,
                'application_number_masked' => 'E2E-APP-****-'.$application->id,
                'decision_summary' => 'Entrada fictícia para lista definitiva.',
                'changed_after_complaint' => false,
                'metadata' => ['pilot' => true],
            ],
        );
    }

    private function applicationStatusHistory(Application $application, ?string $from, string $to): void
    {
        ApplicationStatusHistory::query()->firstOrCreate(
            [
                'application_id' => $application->id,
                'to_status' => $to,
                'reason' => 'pilot_state_of_art',
            ],
            [
                'from_status' => $from,
                'changed_by' => $application->user_id,
            ],
        );
    }

    private function incomeSource(): IncomeSource
    {
        return IncomeSource::query()->firstOrCreate(
            ['code' => 'pilot_employment'],
            [
                'name' => 'Rendimento fictício de trabalho',
                'description' => 'Fonte de rendimento fictícia para piloto.',
                'is_active' => true,
                'sort_order' => 900,
            ],
        );
    }

    private function documentType(): DocumentType
    {
        return DocumentType::query()->first()
            ?? DocumentType::factory()->create([
                'code' => 'pilot_document',
                'name' => 'Documento fictício piloto',
            ]);
    }

    private function requiredDocument(DocumentType $documentType): RequiredDocument
    {
        return RequiredDocument::query()->where('document_type_id', $documentType->id)->first()
            ?? RequiredDocument::factory()->create([
                'document_type_id' => $documentType->id,
                'program_id' => $this->program()->id,
                'contest_id' => $this->contest()->id,
            ]);
    }

    private function contestHousingUnit(HousingUnit $housingUnit): ContestHousingUnit
    {
        return ContestHousingUnit::query()->firstOrCreate(
            [
                'contest_id' => $this->contest()->id,
                'housing_unit_id' => $housingUnit->id,
            ],
            [
                'program_id' => $this->program()->id,
                'status' => 'available',
                'typology' => $housingUnit->typology,
                'bedrooms' => $housingUnit->bedrooms,
                'max_occupants' => 4,
                'min_occupants' => 1,
                'monthly_rent' => $housingUnit->monthly_rent,
                'created_by' => $this->user('e2e.habitacao@example.test')->id,
            ],
        );
    }

    private function program(): Program
    {
        return Program::query()->where('slug', DemoAlcanenaAffordableRentSeeder::PROGRAM_SLUG)->firstOrFail();
    }

    private function contest(): Contest
    {
        return Contest::query()->where('code', DemoAlcanenaAffordableRentSeeder::CONTEST_CODE)->firstOrFail();
    }

    private function municipality(): Municipality
    {
        return Municipality::query()->where('code', DemoAlcanenaAffordableRentSeeder::MUNICIPALITY_CODE)->firstOrFail();
    }

    private function housingUnit(string $code): HousingUnit
    {
        return HousingUnit::query()->where('code', $code)->first()
            ?? HousingUnit::query()->where('code', 'like', 'ALC-DEMO-%')->firstOrFail();
    }

    private function team(string $name): ?MunicipalTeam
    {
        return MunicipalTeam::query()->where('name', $name)->first();
    }

    private function teamForUser(string $email): ?MunicipalTeam
    {
        return $this->user($email)->municipalTeams()->first();
    }

    private function user(string $email): User
    {
        return User::query()->where('email', $email)->firstOrFail();
    }
}
