<?php

namespace App\Services\Municipalities;

use App\Models\Application;
use App\Models\Contract;
use App\Models\HousingUnit;
use App\Models\InspectionChecklistTemplate;
use App\Models\MaintenanceAssignment;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceCost;
use App\Models\MaintenanceIntervention;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceSupplier;
use App\Models\PropertyInspection;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

final class OperationalMunicipalContextService
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function housingUnitForActor(
        User $actor,
        mixed $housingUnitId,
    ): HousingUnit {
        $id = $this->requiredId(
            $housingUnitId,
            'housing_unit_id',
        );

        $housingUnit = $this->municipalScope
            ->housingUnits(
                HousingUnit::query()->whereKey($id),
                $actor,
            )
            ->first();

        if (! $housingUnit instanceof HousingUnit) {
            $this->fail(
                'housing_unit_id',
                'O fogo indicado não está disponível no seu âmbito municipal.',
            );
        }

        $this->municipalityId($housingUnit);

        return $housingUnit;
    }

    public function maintenanceRequestHousingUnit(
        User $actor,
        MaintenanceRequest $request,
    ): HousingUnit {
        if (! $this->municipalScope->ownsMaintenanceRequest(
            $actor,
            $request,
        )) {
            $this->fail(
                'maintenance_request_id',
                'O pedido não está disponível no seu âmbito municipal.',
            );
        }

        return $this->housingUnitForActor(
            $actor,
            $request->getAttribute('housing_unit_id'),
        );
    }

    public function maintenanceAssignmentHousingUnit(
        User $actor,
        MaintenanceAssignment $assignment,
    ): HousingUnit {
        if (! $this->municipalScope->ownsMaintenanceAssignment(
            $actor,
            $assignment,
        )) {
            $this->fail(
                'maintenance_assignment_id',
                'A atribuição não está disponível no seu âmbito municipal.',
            );
        }

        $request = MaintenanceRequest::query()->find(
            $assignment->getAttribute('maintenance_request_id'),
        );

        if (! $request instanceof MaintenanceRequest) {
            $this->fail(
                'maintenance_request_id',
                'A atribuição não possui um pedido válido.',
            );
        }

        return $this->maintenanceRequestHousingUnit(
            $actor,
            $request,
        );
    }

    public function maintenanceInterventionHousingUnit(
        User $actor,
        MaintenanceIntervention $intervention,
    ): HousingUnit {
        if (! $this->municipalScope->ownsMaintenanceIntervention(
            $actor,
            $intervention,
        )) {
            $this->fail(
                'maintenance_intervention_id',
                'A intervenção não está disponível no seu âmbito municipal.',
            );
        }

        $request = MaintenanceRequest::query()->find(
            $intervention->getAttribute('maintenance_request_id'),
        );

        if (! $request instanceof MaintenanceRequest) {
            $this->fail(
                'maintenance_request_id',
                'A intervenção não possui um pedido válido.',
            );
        }

        return $this->maintenanceRequestHousingUnit(
            $actor,
            $request,
        );
    }

    public function maintenanceCostHousingUnit(
        User $actor,
        MaintenanceCost $cost,
    ): HousingUnit {
        if (! $this->municipalScope->ownsMaintenanceCost(
            $actor,
            $cost,
        )) {
            $this->fail(
                'maintenance_cost_id',
                'O custo não está disponível no seu âmbito municipal.',
            );
        }

        $request = MaintenanceRequest::query()->find(
            $cost->getAttribute('maintenance_request_id'),
        );

        if (! $request instanceof MaintenanceRequest) {
            $this->fail(
                'maintenance_request_id',
                'O custo não possui um pedido válido.',
            );
        }

        return $this->maintenanceRequestHousingUnit(
            $actor,
            $request,
        );
    }

    public function propertyInspectionHousingUnit(
        User $actor,
        PropertyInspection $inspection,
    ): HousingUnit {
        if (! $this->municipalScope->ownsPropertyInspection(
            $actor,
            $inspection,
        )) {
            $this->fail(
                'property_inspection_id',
                'A vistoria não está disponível no seu âmbito municipal.',
            );
        }

        return $this->housingUnitForActor(
            $actor,
            $inspection->getAttribute('housing_unit_id'),
        );
    }

    public function categoryForHousingUnit(
        mixed $categoryId,
        HousingUnit $housingUnit,
    ): ?MaintenanceCategory {
        $id = $this->optionalId(
            $categoryId,
            'maintenance_category_id',
        );

        if ($id === null) {
            return null;
        }

        $category = $this->municipalScope
            ->maintenanceCategoriesForMunicipality(
                MaintenanceCategory::query()
                    ->whereKey($id)
                    ->where('is_active', true),
                $this->municipalityId($housingUnit),
            )
            ->first();

        if (! $category instanceof MaintenanceCategory) {
            $this->fail(
                'maintenance_category_id',
                'A categoria não pertence ao Município do fogo.',
            );
        }

        return $category;
    }

    public function supplierForHousingUnit(
        mixed $supplierId,
        HousingUnit $housingUnit,
        string $field = 'maintenance_supplier_id',
    ): ?MaintenanceSupplier {
        $id = $this->optionalId($supplierId, $field);

        if ($id === null) {
            return null;
        }

        $supplier = $this->municipalScope
            ->maintenanceSuppliersForMunicipality(
                MaintenanceSupplier::query()
                    ->whereKey($id)
                    ->where('status', 'active'),
                $this->municipalityId($housingUnit),
            )
            ->first();

        if (! $supplier instanceof MaintenanceSupplier) {
            $this->fail(
                $field,
                'O fornecedor não pertence ao Município do fogo.',
            );
        }

        return $supplier;
    }

    public function templateForHousingUnit(
        mixed $templateId,
        HousingUnit $housingUnit,
    ): ?InspectionChecklistTemplate {
        $id = $this->optionalId(
            $templateId,
            'inspection_checklist_template_id',
        );

        if ($id === null) {
            return null;
        }

        $template = $this->municipalScope
            ->inspectionChecklistTemplatesForMunicipality(
                InspectionChecklistTemplate::query()
                    ->whereKey($id)
                    ->where('is_active', true),
                $this->municipalityId($housingUnit),
            )
            ->first();

        if (! $template instanceof InspectionChecklistTemplate) {
            $this->fail(
                'inspection_checklist_template_id',
                'A checklist não pertence ao Município do fogo.',
            );
        }

        return $template;
    }

    public function municipalUserForHousingUnit(
        mixed $userId,
        HousingUnit $housingUnit,
        string $field,
    ): ?User {
        $id = $this->optionalId($userId, $field);

        if ($id === null) {
            return null;
        }

        $user = User::query()
            ->whereKey($id)
            ->where('status', 'active')
            ->where(
                'municipality_id',
                $this->municipalityId($housingUnit),
            )
            ->first();

        if (! $user instanceof User) {
            $this->fail(
                $field,
                'O utilizador não pertence ao Município do fogo.',
            );
        }

        return $user;
    }

    public function contractForHousingUnit(
        mixed $contractId,
        HousingUnit $housingUnit,
    ): ?Contract {
        $id = $this->optionalId(
            $contractId,
            'lease_contract_id',
        );

        if ($id === null) {
            return null;
        }

        $contract = Contract::query()
            ->whereKey($id)
            ->where(
                'housing_unit_id',
                $housingUnit->getKey(),
            )
            ->first();

        if (! $contract instanceof Contract) {
            $this->fail(
                'lease_contract_id',
                'O contrato não está associado ao fogo indicado.',
            );
        }

        return $contract;
    }

    public function applicationForHousingUnit(
        mixed $applicationId,
        HousingUnit $housingUnit,
    ): ?Application {
        $id = $this->optionalId(
            $applicationId,
            'application_id',
        );

        if ($id === null) {
            return null;
        }

        $municipalityId = $this->municipalityId(
            $housingUnit,
        );

        $application = Application::query()
            ->whereKey($id)
            ->whereHas(
                'program',
                function (Builder $program) use (
                    $municipalityId,
                ): void {
                    $program->where(
                        'municipality_id',
                        $municipalityId,
                    );
                },
            )
            ->first();

        if (! $application instanceof Application) {
            $this->fail(
                'application_id',
                'A candidatura não pertence ao Município do fogo.',
            );
        }

        return $application;
    }

    public function interventionForRequest(
        mixed $interventionId,
        MaintenanceRequest $request,
    ): ?MaintenanceIntervention {
        $id = $this->optionalId(
            $interventionId,
            'maintenance_intervention_id',
        );

        if ($id === null) {
            return null;
        }

        $intervention = MaintenanceIntervention::query()
            ->whereKey($id)
            ->where(
                'maintenance_request_id',
                $request->getKey(),
            )
            ->where(
                'housing_unit_id',
                $request->getAttribute('housing_unit_id'),
            )
            ->first();

        if (! $intervention instanceof MaintenanceIntervention) {
            $this->fail(
                'maintenance_intervention_id',
                'A intervenção não pertence ao pedido indicado.',
            );
        }

        return $intervention;
    }

    public function inspectorIdForHousingUnit(
        User $actor,
        HousingUnit $housingUnit,
        mixed $inspectorUserId,
    ): ?int {
        $providedId = $this->optionalId(
            $inspectorUserId,
            'inspector_user_id',
        );

        if ($providedId !== null) {
            $inspector = $this->municipalUserForHousingUnit(
                $providedId,
                $housingUnit,
                'inspector_user_id',
            );

            return (int) $inspector?->getKey();
        }

        if (
            ($actor->status ?? 'active') === 'active'
            && $actor->municipality_id !== null
            && (int) $actor->municipality_id
                === $this->municipalityId($housingUnit)
        ) {
            return (int) $actor->getKey();
        }

        return null;
    }

    private function municipalityId(
        HousingUnit $housingUnit,
    ): int {
        $value = $housingUnit->getAttribute(
            'municipality_id',
        );

        if (
            (! is_int($value) && ! is_string($value))
            || (int) $value <= 0
        ) {
            $this->fail(
                'housing_unit_id',
                'O fogo não possui um Município válido.',
            );
        }

        return (int) $value;
    }

    private function requiredId(
        mixed $value,
        string $field,
    ): int {
        $id = $this->optionalId($value, $field);

        if ($id === null) {
            $this->fail(
                $field,
                'Este identificador é obrigatório.',
            );
        }

        return $id;
    }

    private function optionalId(
        mixed $value,
        string $field,
    ): ?int {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (
            is_string($value)
            && ctype_digit($value)
            && (int) $value > 0
        ) {
            return (int) $value;
        }

        $this->fail(
            $field,
            'O identificador indicado é inválido.',
        );
    }

    private function fail(
        string $field,
        string $message,
    ): never {
        throw ValidationException::withMessages([
            $field => $message,
        ]);
    }
}
