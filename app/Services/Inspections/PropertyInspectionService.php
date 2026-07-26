<?php

namespace App\Services\Inspections;

use App\Enums\InspectionCondition;
use App\Enums\InspectionStatus;
use App\Enums\InspectionType;
use App\Enums\OfficialNotificationType;
use App\Enums\TechnicalHistoryEventType;
use App\Models\HousingUnit;
use App\Models\InspectionChecklistTemplate;
use App\Models\PropertyInspection;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Maintenance\MaintenanceNotificationService;
use App\Services\Maintenance\MaintenanceNumberService;
use App\Services\Municipalities\OperationalMunicipalContextService;
use App\Services\Properties\PropertyTechnicalHistoryService;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PropertyInspectionService
{
    public function __construct(
        private readonly MaintenanceNumberService $numbers,
        private readonly AuditLogger $auditLogger,
        private readonly PropertyTechnicalHistoryService $history,
        private readonly MaintenanceNotificationService $notifications,
        private readonly OperationalMunicipalContextService $municipalContext,
    ) {}

    /**
     * @param  array<string, bool|float|int|string|null>  $data
     */
    public function store(User $actor, array $data): PropertyInspection
    {
        return DB::transaction(function () use ($actor, $data) {
            $housingUnit = $this->municipalContext
                ->housingUnitForActor(
                    $actor,
                    $data['housing_unit_id'] ?? null,
                );

            $contract = $this->municipalContext
                ->contractForHousingUnit(
                    $data['lease_contract_id'] ?? null,
                    $housingUnit,
                );

            $application = $this->municipalContext
                ->applicationForHousingUnit(
                    $data['application_id'] ?? null,
                    $housingUnit,
                );

            $template = $this->municipalContext
                ->templateForHousingUnit(
                    $data[
                        'inspection_checklist_template_id'
                    ] ?? null,
                    $housingUnit,
                );

            $inspectorId = $this->municipalContext
                ->inspectorIdForHousingUnit(
                    $actor,
                    $housingUnit,
                    $data['inspector_user_id'] ?? null,
                );

            $inspection = new PropertyInspection([
                'housing_unit_id' => $housingUnit->id,
                'lease_contract_id' => $contract?->id,
                'application_id' => $application?->id,
                'inspection_checklist_template_id' => $template?->id,
                'inspection_type' => $this->inspectionTypeFromData($data),
                'scheduled_for' => $data['scheduled_for'] ?? null,
                'inspector_user_id' => $inspectorId,
                'summary' => $data['summary'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $inspection->forceFill([
                'inspection_number' => $this->numbers->inspectionNumber(),
                'status' => ! empty($data['scheduled_for'])
                    ? InspectionStatus::Scheduled
                    : InspectionStatus::Draft,
            ])->save();

            if (
                $template instanceof InspectionChecklistTemplate
            ) {
                $this->copyTemplateItems(
                    $inspection,
                    $template,
                );
            }

            $this->auditLogger->record(
                AuditEvents::CREATE,
                $inspection,
                'inspections',
                'inspection_created',
                'Vistoria criada.',
            );

            $this->history->record(
                $housingUnit,
                TechnicalHistoryEventType::InspectionCreated,
                'Vistoria criada',
                $this->inspectionTypeLabel($inspection),
                $actor,
                $inspection->leaseContract,
                inspection: $inspection,
            );

            $this->notifications->inspectionStatus(
                $inspection,
                OfficialNotificationType::InspectionScheduled,
                'Vistoria agendada',
                'Foi registada uma vistoria associada à sua habitação.',
                $actor,
            );

            return $inspection->refresh();
        });
    }

    public function start(PropertyInspection $inspection, User $actor): PropertyInspection
    {
        $this->municipalContext
            ->propertyInspectionHousingUnit(
                $actor,
                $inspection,
            );

        $inspection->forceFill(['status' => InspectionStatus::InProgress, 'started_at' => now()])->save();

        return $inspection->refresh();
    }

    /**
     * @param  array<string, array<int, array<string, bool|int|string|null>>|bool|float|int|string|null>  $data
     */
    public function complete(PropertyInspection $inspection, User $actor, array $data): PropertyInspection
    {
        $this->municipalContext
            ->propertyInspectionHousingUnit(
                $actor,
                $inspection,
            );

        $inspection->forceFill([
            'status' => InspectionStatus::Completed,
            'completed_at' => now(),
            'general_condition' => $this->inspectionConditionFromData($data),
            'summary' => $data['summary'],
            'recommendations' => $data['recommendations'] ?? null,
            'tenant_present' => (bool) ($data['tenant_present'] ?? false),
            'tenant_observations' => $data['tenant_observations'] ?? null,
        ])->save();

        foreach ($this->itemsFromData($data) as $item) {
            if (! empty($item['id'])) {
                $inspection->items()->whereKey($item['id'])->update([
                    'condition' => $item['condition'] ?? null,
                    'observations' => $item['observations'] ?? null,
                    'requires_maintenance' => (bool) ($item['requires_maintenance'] ?? false),
                ]);
            }
        }

        $this->auditLogger->record(AuditEvents::UPDATE, $inspection, 'inspections', 'inspection_completed', 'Vistoria concluída.');
        $this->history->record($this->housingUnitForInspection($inspection), TechnicalHistoryEventType::InspectionCompleted, 'Vistoria concluída', $inspection->summary, $actor, $inspection->leaseContract, inspection: $inspection, visibleToTenant: true);
        $this->notifications->inspectionStatus($inspection, OfficialNotificationType::InspectionCompleted, 'Vistoria concluída', 'A vistoria associada à sua habitação foi concluída.', $actor);

        return $inspection->refresh();
    }

    public function validate(PropertyInspection $inspection, User $actor): PropertyInspection
    {
        $this->municipalContext
            ->propertyInspectionHousingUnit(
                $actor,
                $inspection,
            );

        $inspection->forceFill([
            'status' => InspectionStatus::Validated,
            'validated_at' => now(),
            'validated_by' => $actor->id,
            'tenant_visible' => true,
        ])->save();

        $this->auditLogger->record(AuditEvents::APPROVE, $inspection, 'inspections', 'inspection_validated', 'Vistoria validada.');
        $this->history->record($this->housingUnitForInspection($inspection), TechnicalHistoryEventType::InspectionValidated, 'Vistoria validada', $inspection->summary, $actor, $inspection->leaseContract, inspection: $inspection, visibleToTenant: true);

        return $inspection->refresh();
    }

    public function close(PropertyInspection $inspection, User $actor): PropertyInspection
    {
        $this->municipalContext
            ->propertyInspectionHousingUnit(
                $actor,
                $inspection,
            );

        $inspection->forceFill(['status' => InspectionStatus::Closed])->save();

        return $inspection->refresh();
    }

    public function cancel(PropertyInspection $inspection, User $actor): PropertyInspection
    {
        $this->municipalContext
            ->propertyInspectionHousingUnit(
                $actor,
                $inspection,
            );

        $inspection->forceFill(['status' => InspectionStatus::Cancelled])->save();

        return $inspection->refresh();
    }

    private function copyTemplateItems(
        PropertyInspection $inspection,
        InspectionChecklistTemplate $template,
    ): void {
        $template->loadMissing('items');

        foreach ($template->items as $item) {
            $inspection->items()->create([
                'inspection_checklist_template_item_id' => $item->id,
                'code' => $item->code,
                'label' => $item->label,
                'area' => $item->area,
                'sort_order' => $item->sort_order,
            ]);
        }
    }

    private function inspectionTypeLabel(PropertyInspection $inspection): string
    {
        $type = $inspection->getAttribute('inspection_type');

        if ($type instanceof InspectionType) {
            return $type->label();
        }

        return is_string($type) ? InspectionType::from($type)->label() : '';
    }

    /**
     * @param  array<string, bool|float|int|string|null>  $data
     */
    private function inspectionTypeFromData(array $data): InspectionType
    {
        $value = $data['inspection_type'] ?? null;

        if (! is_int($value) && ! is_string($value)) {
            throw ValidationException::withMessages([
                'inspection_type' => 'Tipo de vistoria inválido.',
            ]);
        }

        return InspectionType::from($value);
    }

    /**
     * @param  array<string, array<int, array<string, bool|int|string|null>>|bool|float|int|string|null>  $data
     */
    private function inspectionConditionFromData(array $data): InspectionCondition
    {
        $value = $data['general_condition'] ?? null;

        if (! is_int($value) && ! is_string($value)) {
            throw ValidationException::withMessages([
                'general_condition' => 'Condição geral inválida.',
            ]);
        }

        return InspectionCondition::from($value);
    }

    /**
     * @param  array<string, array<int, array<string, bool|int|string|null>>|bool|float|int|string|null>  $data
     * @return array<int, array<string, bool|int|string|null>>
     */
    private function itemsFromData(array $data): array
    {
        $items = $data['items'] ?? [];

        return is_array($items) ? $items : [];
    }

    private function housingUnitForInspection(PropertyInspection $inspection): HousingUnit
    {
        $housingUnit = $inspection->housingUnit;

        if (! $housingUnit instanceof HousingUnit) {
            throw ValidationException::withMessages([
                'housing_unit' => 'A vistoria não tem fogo associado.',
            ]);
        }

        return $housingUnit;
    }
}
