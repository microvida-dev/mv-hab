<?php

namespace App\Services\Dashboard\Timeline\Providers;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\AllocationStatus;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Models\Allocation;
use App\Models\User;
use App\Services\Dashboard\Timeline\TimelineEventFactory;
use App\Services\Dashboard\Timeline\TimelineProviderInterface;
use Illuminate\Support\Collection;

class AllocationTimelineProvider implements TimelineProviderInterface
{
    public function __construct(
        private readonly TimelineEventFactory $factory = new TimelineEventFactory(),
    ) {}

    public function forUser(User $user, array $dashboard = []): array
    {
        if (! $user->hasPermission('allocations.view')) {
            return [];
        }

        return Allocation::query()
            ->with(['application', 'candidate', 'housingUnit', 'contest'])
            ->whereIn('status', [
                AllocationStatus::Offered->value,
                AllocationStatus::Accepted->value,
                AllocationStatus::ReadyForContract->value,
            ])
            ->where(function ($query): void {
                $query
                    ->whereNotNull('offered_at')
                    ->orWhereNotNull('accepted_at')
                    ->orWhereNotNull('ready_for_contract_at')
                    ->orWhereNotNull('acceptance_deadline_at');
            })
            ->orderByRaw('COALESCE(ready_for_contract_at, acceptance_deadline_at, accepted_at, offered_at, created_at) asc')
            ->limit(30)
            ->get()
            ->flatMap(fn (Allocation $allocation): Collection => $this->eventsForAllocation($allocation))
            ->values()
            ->all();
    }

    /** @return Collection<int, TimelineEvent> */
    private function eventsForAllocation(Allocation $allocation): Collection
    {
        return collect()
            ->when($allocation->offered_at, fn (Collection $events): Collection => $events->push($this->offerEvent($allocation)))
            ->when($allocation->accepted_at, fn (Collection $events): Collection => $events->push($this->acceptedEvent($allocation)))
            ->when($allocation->ready_for_contract_at, fn (Collection $events): Collection => $events->push($this->readyForContractEvent($allocation)));
    }

    private function offerEvent(Allocation $allocation): TimelineEvent
    {
        return $this->factory->make(
            id: 'allocation-offer-'.$allocation->getKey(),
            type: TimelineType::AllocationOffer,
            title: 'Oferta de atribuição emitida',
            description: $this->description($allocation),
            route: route('backoffice.allocation.allocations.index'),
            datetime: $allocation->acceptance_deadline_at ?? $allocation->offered_at,
            priority: $allocation->acceptance_deadline_at?->isPast() ? TimelinePriority::High : TimelinePriority::Medium,
            icon: 'housing',
            tone: $allocation->acceptance_deadline_at?->isPast() ? 'warning' : 'info',
            workspace: TimelineWorkspace::Applications,
            metadata: $this->metadata($allocation),
        );
    }

    private function acceptedEvent(Allocation $allocation): TimelineEvent
    {
        return $this->factory->make(
            id: 'allocation-accepted-'.$allocation->getKey(),
            type: TimelineType::AllocationAccepted,
            title: 'Oferta de atribuição aceite',
            description: $this->description($allocation),
            route: route('backoffice.allocation.allocations.index'),
            datetime: $allocation->accepted_at,
            priority: TimelinePriority::Medium,
            icon: 'check-circle',
            tone: 'success',
            workspace: TimelineWorkspace::Applications,
            metadata: $this->metadata($allocation),
        );
    }

    private function readyForContractEvent(Allocation $allocation): TimelineEvent
    {
        return $this->factory->make(
            id: 'allocation-ready-for-contract-'.$allocation->getKey(),
            type: TimelineType::AllocationReadyForContract,
            title: 'Atribuição pronta para contrato',
            description: $this->description($allocation),
            route: route('backoffice.allocation.allocations.index'),
            datetime: $allocation->ready_for_contract_at,
            priority: TimelinePriority::High,
            icon: 'contract',
            tone: 'warning',
            workspace: TimelineWorkspace::Applications,
            metadata: $this->metadata($allocation),
        );
    }

    private function description(Allocation $allocation): string
    {
        $application = $allocation->application?->application_number ?? 'Candidatura';
        $candidate = $allocation->candidate?->name ?? 'Candidato';
        $housing = $allocation->housingUnit?->reference ?? $allocation->housingUnit?->code ?? 'Habitação';

        return trim("{$application} · {$candidate} · {$housing}");
    }

    /** @return array<string, mixed> */
    private function metadata(Allocation $allocation): array
    {
        return [
            'allocation_id' => $allocation->getKey(),
            'application_id' => $allocation->application_id,
            'application_number' => $allocation->application?->application_number,
            'candidate_id' => $allocation->user_id,
            'candidate_name' => $allocation->candidate?->name,
            'housing_unit_id' => $allocation->housing_unit_id,
            'contest_id' => $allocation->contest_id,
            'contest_title' => $allocation->contest?->title,
            'status' => $allocation->status?->value ?? $allocation->status,
            'rank_position' => $allocation->rank_position,
            'acceptance_deadline_at' => $allocation->acceptance_deadline_at?->toIso8601String(),
        ];
    }
}
