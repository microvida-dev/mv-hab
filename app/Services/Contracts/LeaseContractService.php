<?php

namespace App\Services\Contracts;

use App\Enums\AllocationStatus;
use App\Enums\ContractStatus;
use App\Enums\ContractTemplateStatus;
use App\Enums\RentCalculationStatus;
use App\Models\AdhesionRegistration;
use App\Models\Allocation;
use App\Models\AllocationOffer;
use App\Models\Application;
use App\Models\ContestHousingUnit;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\HousingUnit;
use App\Models\Municipality;
use App\Models\Program;
use App\Models\RentCalculation;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaseContractService
{
    public function __construct(
        private readonly LeaseContractNumberService $numberService,
        private readonly ContractClauseService $clauseService,
        private readonly ContractDepositService $depositService,
        private readonly LeaseContractStatusService $statusService,
        private readonly ContractNotificationService $notificationService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createFromAllocation(Allocation $allocation, RentCalculation $calculation, ContractTemplate $template, User $actor, array $data): Contract
    {
        $allocation->loadMissing(['application.adhesionRegistration', 'application.household', 'housingUnit', 'contestHousingUnit', 'activeOffer', 'program.municipality', 'contest']);

        $this->assertCanCreate($allocation, $calculation, $template);

        return DB::transaction(function () use ($allocation, $calculation, $template, $actor, $data) {
            /** @var Application $application */
            $application = $allocation->getRelationValue('application');
            /** @var AdhesionRegistration|null $registration */
            $registration = $application->getRelationValue('adhesionRegistration');
            /** @var HousingUnit $housingUnit */
            $housingUnit = $allocation->getRelationValue('housingUnit');
            /** @var Program|null $program */
            $program = $allocation->getRelationValue('program');
            /** @var Municipality|null $municipality */
            $municipality = $program?->getRelationValue('municipality');
            /** @var AllocationOffer|null $activeOffer */
            $activeOffer = $allocation->getRelationValue('activeOffer');
            /** @var User|null $candidate */
            $candidate = $allocation->getRelationValue('candidate');
            /** @var ContestHousingUnit|null $contestHousingUnit */
            $contestHousingUnit = $allocation->getRelationValue('contestHousingUnit');
            $monthlyRent = (float) $calculation->applicable_rent;
            $depositAmount = (float) ($calculation->deposit_amount ?? 0);

            $contract = new Contract([
                'citizen_id' => null,
                'housing_unit_id' => $housingUnit->id,
                'program_id' => $allocation->program_id,
                'contest_id' => $allocation->contest_id,
                'application_id' => $allocation->application_id,
                'allocation_offer_id' => $activeOffer?->id,
                'user_id' => $allocation->user_id,
                'household_id' => $application->household_id,
                'contest_housing_unit_id' => $allocation->contest_housing_unit_id,
                'contract_template_id' => $template->id,
                'tenant_name' => $registration->full_name ?? $candidate?->name,
                'tenant_identification_number' => $registration?->document_number,
                'tenant_tax_number' => $registration?->nif,
                'tenant_email' => $registration->email ?? $candidate?->email,
                'tenant_phone' => $registration->mobile_phone ?? $registration?->phone,
                'tenant_address' => trim(collect([$registration?->address, $registration?->postal_code, $registration?->city])->filter()->implode(', ')),
                'landlord_name' => $municipality->name ?? 'Município MV HAB',
                'landlord_tax_number' => null,
                'landlord_address' => null,
                'landlord_representative' => null,
                'housing_address' => $housingUnit->address,
                'housing_typology' => $contestHousingUnit->typology ?? $housingUnit->typology,
                'housing_description' => $housingUnit->code,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'duration_months' => $data['duration_months'] ?? null,
                'renewal_allowed' => false,
                'payment_day' => $data['payment_day'] ?? null,
                'payment_method_description' => 'Pagamento de rendas fora do âmbito da Sprint 13.',
                'special_conditions' => $data['special_conditions'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
            ]);

            $contract->forceFill([
                'contract_number' => $this->numberService->generate(),
                'allocation_id' => $allocation->id,
                'rent_calculation_id' => $calculation->id,
                'status' => ContractStatus::Preparation,
                'monthly_rent' => $monthlyRent,
                'deposit_amount' => $depositAmount,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ])->save();

            $calculation->forceFill(['contract_id' => $contract->id])->save();

            $contract->parties()->create([
                'user_id' => $allocation->user_id,
                'party_type' => 'tenant',
                'name' => $contract->tenant_name,
                'identification_number' => $contract->tenant_identification_number,
                'tax_number' => $contract->tenant_tax_number,
                'email' => $contract->tenant_email,
                'phone' => $contract->tenant_phone,
                'address' => $contract->tenant_address,
                'sort_order' => 1,
            ]);

            $contract->parties()->create([
                'party_type' => 'landlord',
                'name' => $contract->landlord_name,
                'tax_number' => $contract->landlord_tax_number,
                'address' => $contract->landlord_address,
                'representative_name' => $contract->landlord_representative,
                'sort_order' => 2,
            ]);

            $this->clauseService->snapshotForContract($contract, $template);
            $this->depositService->createForContract($contract, $actor);
            $this->statusService->transition($contract, ContractStatus::Preparation, $actor, 'Contrato criado em preparação.');
            $this->auditLogger->record(AuditEvents::CREATE, $contract, 'contracts', 'lease_contract_create', 'Contrato criado a partir de atribuição aceite.');
            $this->notificationService->preparationStarted($contract->refresh(), $actor);

            return $contract->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updatePreparation(Contract $contract, array $data, User $actor): Contract
    {
        if ($contract->status !== ContractStatus::Preparation) {
            throw ValidationException::withMessages(['contract' => 'Só contratos em preparação podem ser editados nesta sprint.']);
        }

        $contract->fill($data);
        $contract->forceFill(['updated_by' => $actor->id])->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $contract, 'contracts', 'lease_contract_update', 'Contrato em preparação atualizado.');

        return $contract->refresh();
    }

    public function issue(Contract $contract, User $actor, ?string $notes = null): Contract
    {
        $contract->loadMissing(['deposit', 'generatedDocuments']);

        if ($contract->generatedDocuments()->count() === 0) {
            throw ValidationException::withMessages(['document' => 'Gere o documento contratual antes de emitir o contrato.']);
        }

        if ($contract->deposit === null) {
            throw ValidationException::withMessages(['deposit' => 'O contrato deve ter caução registada.']);
        }

        $issued = $this->statusService->transition($contract, ContractStatus::Issued, $actor, $notes);
        $this->notificationService->issued($issued, $actor);

        return $issued;
    }

    public function cancel(Contract $contract, User $actor, string $reason): Contract
    {
        return $this->statusService->transition($contract, ContractStatus::Cancelled, $actor, $reason);
    }

    private function assertCanCreate(Allocation $allocation, RentCalculation $calculation, ContractTemplate $template): void
    {
        if (! in_array($this->allocationStatus($allocation), [AllocationStatus::Accepted, AllocationStatus::ReadyForContract], true)) {
            throw ValidationException::withMessages(['allocation_id' => 'A atribuição deve estar aceite ou pronta para contrato.']);
        }

        if ($allocation->leaseContract()->exists()) {
            throw ValidationException::withMessages(['allocation_id' => 'Já existe contrato para esta atribuição.']);
        }

        if ($calculation->allocation_id !== $allocation->id || $this->rentCalculationStatus($calculation) !== RentCalculationStatus::Approved) {
            throw ValidationException::withMessages(['rent_calculation_id' => 'O cálculo de renda deve estar aprovado e associado à atribuição.']);
        }

        if ($this->contractTemplateStatus($template) !== ContractTemplateStatus::Active) {
            throw ValidationException::withMessages(['contract_template_id' => 'A minuta contratual deve estar ativa.']);
        }
    }

    private function allocationStatus(Allocation $allocation): ?AllocationStatus
    {
        $status = $allocation->getAttribute('status');

        return $status instanceof AllocationStatus
            ? $status
            : AllocationStatus::tryFrom((string) $status);
    }

    private function rentCalculationStatus(RentCalculation $calculation): ?RentCalculationStatus
    {
        $status = $calculation->getAttribute('status');

        return $status instanceof RentCalculationStatus
            ? $status
            : RentCalculationStatus::tryFrom((string) $status);
    }

    private function contractTemplateStatus(ContractTemplate $template): ?ContractTemplateStatus
    {
        $status = $template->getAttribute('status');

        return $status instanceof ContractTemplateStatus
            ? $status
            : ContractTemplateStatus::tryFrom((string) $status);
    }
}
