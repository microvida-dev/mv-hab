<?php

namespace App\Services\Dashboard\Timeline\Providers;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\AllocationStatus;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Models\Allocation;
use App\Models\Application;
use App\Models\HousingUnit;
use App\Models\User;
use App\Services\Dashboard\Timeline\BaseTimelineProvider;
use App\Services\Dashboard\Timeline\TimelineEventFactory;
use Illuminate\Support\Collection;

class AllocationTimelineProvider extends BaseTimelineProvider
{
    public function __construct(
        private readonly TimelineEventFactory $factory = new TimelineEventFactory,
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
        return trim(sprintf(
            '%s · %s · %s',
            $this->applicationNumber($allocation),
            $this->candidateName($allocation),
            $this->housingCode($allocation),
        ));
    }

    /** @return array<string, mixed> */
    private function metadata(Allocation $allocation): array
    {
        $application = $this->application($allocation);
        $candidate = $this->candidate($allocation);

        return [
            'allocation_id' => $allocation->getKey(),
            'application_id' => $allocation->application_id,
            'application_number' => $application?->application_number,
            'candidate_id' => $allocation->user_id,
            'candidate_name' => $candidate?->name,
            'housing_unit_id' => $allocation->housing_unit_id,
            'contest_id' => $allocation->contest_id,
            'contest_title' => $allocation->contest?->title,
            'status' => $allocation->status->value,
            'rank_position' => $allocation->rank_position,
            'acceptance_deadline_at' => $this->iso($allocation->acceptance_deadline_at),
        ];
    }

    private function application(Allocation $allocation): ?Application
    {
        $relation = $allocation->relationLoaded('application')
            ? $allocation->getRelation('application')
            : null;

        return $relation instanceof Application ? $relation : null;
    }

    private function applicationNumber(Allocation $allocation): string
    {
        $application = $this->application($allocation);

        return $application === null
            ? 'Candidatura'
            : ($application->application_number ?? 'Candidatura');
    }

    private function candidate(Allocation $allocation): ?User
    {
        $relation = $allocation->relationLoaded('candidate')
            ? $allocation->getRelation('candidate')
            : null;

        return $relation instanceof User ? $relation : null;
    }

    private function candidateName(Allocation $allocation): string
    {
        $candidate = $this->candidate($allocation);

        return $candidate === null ? 'Candidato' : $candidate->name;
    }

    private function housingUnit(Allocation $allocation): ?HousingUnit
    {
        $relation = $allocation->relationLoaded('housingUnit')
            ? $allocation->getRelation('housingUnit')
            : null;

        return $relation instanceof HousingUnit ? $relation : null;
    }

    private function housingCode(Allocation $allocation): string
    {
        $housingUnit = $this->housingUnit($allocation);

        return $housingUnit === null ? 'Habitação' : $housingUnit->code;
    }
}
