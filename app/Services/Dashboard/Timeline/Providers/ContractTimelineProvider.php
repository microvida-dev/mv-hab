<?php

namespace App\Services\Dashboard\Timeline\Providers;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\ContractStatus;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Models\Contract;
use App\Models\User;
use App\Services\Dashboard\Timeline\TimelineProviderInterface;
use App\Services\Dashboard\Timeline\TimelineEventFactory;

class ContractTimelineProvider implements TimelineProviderInterface
{
    public function __construct(
        private readonly TimelineEventFactory $factory = new TimelineEventFactory(),
    ) {}

    public function forUser(User $user, array $dashboard = []): array
    {
        if (! $user->hasPermission('contracts.view')) {
            return [];
        }

        return Contract::query()
            ->with(['candidate', 'housingUnit'])
            ->whereIn('status', [
                ContractStatus::Issued->value,
                ContractStatus::Signed->value,
                ContractStatus::Active->value,
                ContractStatus::Suspended->value,
                ContractStatus::Terminated->value,
                ContractStatus::Ended->value,
            ])
            ->where(function ($query): void {
                $query
                    ->whereNotNull('issued_at')
                    ->orWhereNotNull('signed_at')
                    ->orWhereNotNull('activated_at')
                    ->orWhereNotNull('suspended_at')
                    ->orWhereNotNull('terminated_at');
            })
            ->orderByRaw('COALESCE(issued_at, signed_at, activated_at, suspended_at, terminated_at, updated_at) asc')
            ->limit(30)
            ->get()
            ->map(fn (Contract $contract): TimelineEvent => $this->eventForContract($contract))
            ->all();
    }

    private function eventForContract(Contract $contract): TimelineEvent
    {
        return match ($contract->status) {
            ContractStatus::Issued => $this->issuedEvent($contract),
            ContractStatus::Signed => $this->signedEvent($contract),
            ContractStatus::Active => $this->activeEvent($contract),
            ContractStatus::Suspended => $this->suspendedEvent($contract),
            ContractStatus::Terminated,
            ContractStatus::Ended => $this->terminatedEvent($contract),
            default => $this->issuedEvent($contract),
        };
    }

    private function issuedEvent(Contract $contract): TimelineEvent
    {
        return $this->factory->make(
            id: 'contract-issued-'.$contract->getKey(),
            type: TimelineType::ContractIssued,
            title: 'Contrato emitido',
            description: $this->description($contract),
            route: route('backoffice.contracts.leases.show', $contract),
            datetime: $contract->issued_at ?? $contract->updated_at,
            priority: TimelinePriority::Medium,
            icon: 'contract',
            tone: 'info',
            workspace: TimelineWorkspace::Tenant,
            metadata: $this->metadata($contract),
        );
    }

    private function signedEvent(Contract $contract): TimelineEvent
    {
        return $this->factory->make(
            id: 'contract-signed-'.$contract->getKey(),
            type: TimelineType::ContractSigned,
            title: 'Contrato assinado',
            description: $this->description($contract),
            route: route('backoffice.contracts.leases.show', $contract),
            datetime: $contract->signed_at ?? $contract->updated_at,
            priority: TimelinePriority::Medium,
            icon: 'signature',
            tone: 'success',
            workspace: TimelineWorkspace::Tenant,
            metadata: $this->metadata($contract),
        );
    }

    private function activeEvent(Contract $contract): TimelineEvent
    {
        return $this->factory->make(
            id: 'contract-active-'.$contract->getKey(),
            type: TimelineType::ContractActive,
            title: 'Contrato ativo',
            description: $this->description($contract),
            route: route('backoffice.contracts.leases.show', $contract),
            datetime: $contract->activated_at ?? $contract->updated_at,
            priority: TimelinePriority::Low,
            icon: 'check-circle',
            tone: 'success',
            workspace: TimelineWorkspace::Tenant,
            metadata: $this->metadata($contract),
        );
    }

    private function suspendedEvent(Contract $contract): TimelineEvent
    {
        return $this->factory->make(
            id: 'contract-suspended-'.$contract->getKey(),
            type: TimelineType::ContractSuspended,
            title: 'Contrato suspenso',
            description: $this->description($contract),
            route: route('backoffice.contracts.leases.show', $contract),
            datetime: $contract->suspended_at ?? $contract->updated_at,
            priority: TimelinePriority::High,
            icon: 'warning',
            tone: 'warning',
            workspace: TimelineWorkspace::Tenant,
            metadata: $this->metadata($contract),
        );
    }

    private function terminatedEvent(Contract $contract): TimelineEvent
    {
        return $this->factory->make(
            id: 'contract-terminated-'.$contract->getKey(),
            type: TimelineType::ContractTerminated,
            title: 'Contrato terminado',
            description: $this->description($contract),
            route: route('backoffice.contracts.leases.show', $contract),
            datetime: $contract->terminated_at ?? $contract->updated_at,
            priority: TimelinePriority::Medium,
            icon: 'archive',
            tone: 'neutral',
            workspace: TimelineWorkspace::Tenant,
            metadata: $this->metadata($contract),
        );
    }

    private function description(Contract $contract): string
    {
        $number = $contract->contract_number ?? 'Contrato';
        $candidate = $contract->candidate?->name ?? 'Inquilino';
        $housing = $contract->housingUnit?->reference ?? $contract->housingUnit?->code ?? 'Habitação';

        return trim("{$number} · {$candidate} · {$housing}");
    }

    /**
     * @return array<string, mixed>
     */
    private function metadata(Contract $contract): array
    {
        return [
            'contract_id' => $contract->getKey(),
            'contract_number' => $contract->contract_number,
            'user_id' => $contract->user_id,
            'candidate_name' => $contract->candidate?->name,
            'housing_unit_id' => $contract->housing_unit_id,
            'status' => $contract->status?->value ?? $contract->status,
            'monthly_rent' => $contract->monthly_rent,
            'deposit_amount' => $contract->deposit_amount,
            'start_date' => $contract->start_date?->toDateString(),
            'end_date' => $contract->end_date?->toDateString(),
        ];
    }
}
