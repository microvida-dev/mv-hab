<?php

namespace App\Services\Maintenance;

use App\Enums\ContractStatus;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceRequestStatus;
use App\Enums\MaintenanceSource;
use App\Enums\MaintenanceUrgency;
use App\Enums\OfficialNotificationType;
use App\Enums\TechnicalHistoryEventType;
use App\Models\Contract;
use App\Models\HousingUnit;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Properties\PropertyTechnicalHistoryService;
use App\Support\AuditEvents;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MaintenanceRequestService
{
    public function __construct(
        private readonly MaintenanceNumberService $numbers,
        private readonly MaintenanceAttachmentService $attachments,
        private readonly PropertyTechnicalHistoryService $history,
        private readonly MaintenanceNotificationService $notifications,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, array<int, UploadedFile>|bool|float|int|string|null>  $data
     */
    public function createFromTenant(User $tenant, array $data): MaintenanceRequest
    {
        unset($data['status'], $data['technical_priority'], $data['user_id'], $data['source'], $data['created_by'], $data['updated_by']);

        $contract = $this->resolveTenantContract($tenant, $data);

        return $this->create($tenant, array_merge($data, [
            'lease_contract_id' => $contract->id,
            'housing_unit_id' => $contract->housing_unit_id,
            'application_id' => $contract->application_id,
            'user_id' => $tenant->id,
            'source' => MaintenanceSource::Tenant->value,
        ]), visibleToTenant: true);
    }

    /**
     * @param  array<string, array<int, UploadedFile>|bool|float|int|string|null>  $data
     */
    public function createFromBackoffice(User $actor, array $data): MaintenanceRequest
    {
        return $this->create($actor, array_merge($data, [
            'source' => $data['source'] ?? MaintenanceSource::MunicipalTechnician->value,
            'created_by' => $actor->id,
        ]), visibleToTenant: false);
    }

    /**
     * @param  array<string, array<int, UploadedFile>|bool|float|int|string|null>  $data
     */
    private function create(User $actor, array $data, bool $visibleToTenant): MaintenanceRequest
    {
        return DB::transaction(function () use ($actor, $data, $visibleToTenant) {
            $request = MaintenanceRequest::query()->create([
                'request_number' => $this->numbers->requestNumber(),
                'housing_unit_id' => $data['housing_unit_id'],
                'lease_contract_id' => $data['lease_contract_id'] ?? null,
                'application_id' => $data['application_id'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'maintenance_category_id' => $data['maintenance_category_id'] ?? null,
                'source' => $this->sourceFromData($data),
                'title' => $data['title'],
                'description' => $data['description'],
                'location_in_property' => $data['location_in_property'] ?? null,
                'tenant_availability' => $data['tenant_availability'] ?? null,
                'access_instructions' => $data['access_instructions'] ?? null,
                'priority' => MaintenancePriority::Normal,
                'urgency' => $this->urgencyFromData($data, 'urgency', MaintenanceUrgency::Normal),
                'technical_priority' => isset($data['technical_priority']) ? $this->urgencyFromData($data, 'technical_priority') : null,
                'status' => MaintenanceRequestStatus::New,
                'reported_at' => now(),
                'created_by' => $data['created_by'] ?? $actor->id,
            ]);

            $request->statusHistories()->create([
                'from_status' => null,
                'to_status' => MaintenanceRequestStatus::New,
                'reason' => 'Criação do pedido.',
                'changed_by' => $actor->id,
                'changed_at' => now(),
            ]);

            foreach ($this->attachmentsFromData($data) as $file) {
                $this->attachments->storeForRequest($request, $file, $actor, visibleToTenant: $visibleToTenant);
            }

            $this->history->record(
                $this->housingUnitForRequest($request),
                TechnicalHistoryEventType::MaintenanceRequestCreated,
                'Pedido de manutenção criado',
                $request->title,
                $actor,
                $request->leaseContract,
                $request,
                visibleToTenant: true,
            );

            $this->auditLogger->record(
                AuditEvents::CREATE,
                $request,
                'maintenance_requests',
                'maintenance_request_created',
                'Pedido de manutenção criado.',
            );

            $this->notifications->maintenanceStatus(
                $request,
                OfficialNotificationType::MaintenanceRequestCreated,
                'Pedido de manutenção recebido',
                'O pedido '.$request->request_number.' foi registado na plataforma.',
                $actor,
            );

            return $request->refresh();
        });
    }

    private function housingUnitForRequest(MaintenanceRequest $request): HousingUnit
    {
        $housingUnit = $request->housingUnit;

        if (! $housingUnit instanceof HousingUnit) {
            throw ValidationException::withMessages([
                'housing_unit' => 'O pedido de manutenção não tem fogo associado.',
            ]);
        }

        return $housingUnit;
    }

    /**
     * @param  array<string, array<int, UploadedFile>|bool|float|int|string|null>  $data
     */
    private function sourceFromData(array $data): MaintenanceSource
    {
        $value = $data['source'] ?? MaintenanceSource::MunicipalTechnician->value;

        if (! is_int($value) && ! is_string($value)) {
            throw ValidationException::withMessages([
                'source' => 'Origem do pedido inválida.',
            ]);
        }

        return MaintenanceSource::from($value);
    }

    /**
     * @param  array<string, array<int, UploadedFile>|bool|float|int|string|null>  $data
     */
    private function urgencyFromData(array $data, string $key, ?MaintenanceUrgency $default = null): MaintenanceUrgency
    {
        $value = $data[$key] ?? $default?->value;

        if (! is_int($value) && ! is_string($value)) {
            throw ValidationException::withMessages([
                $key => 'Urgência inválida.',
            ]);
        }

        return MaintenanceUrgency::from($value);
    }

    /**
     * @param  array<string, array<int, UploadedFile>|bool|float|int|string|null>  $data
     * @return array<int, UploadedFile>
     */
    private function attachmentsFromData(array $data): array
    {
        $attachments = $data['attachments'] ?? [];

        if (! is_array($attachments)) {
            return [];
        }

        return array_values($attachments);
    }

    /**
     * @return Builder<MaintenanceRequest>
     */
    public function tenantScope(User $tenant): Builder
    {
        return MaintenanceRequest::query()->where('user_id', $tenant->id);
    }

    /**
     * @param  array<string, array<int, UploadedFile>|bool|float|int|string|null>  $data
     */
    private function resolveTenantContract(User $tenant, array $data): Contract
    {
        $query = Contract::query()
            ->forCandidate($tenant)
            ->where('status', ContractStatus::Active->value);

        if (! empty($data['lease_contract_id'])) {
            $query->whereKey($data['lease_contract_id']);
        }

        if (! empty($data['housing_unit_id'])) {
            $query->where('housing_unit_id', $data['housing_unit_id']);
        }

        $contract = $query->latest('activated_at')->first();

        if (! $contract) {
            throw ValidationException::withMessages([
                'lease_contract_id' => 'Não existe contrato ativo associado à sua área para criar este pedido.',
            ]);
        }

        return $contract;
    }
}
