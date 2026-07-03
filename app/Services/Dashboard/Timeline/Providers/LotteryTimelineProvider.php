<?php

namespace App\Services\Dashboard\Timeline\Providers;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Enums\LotteryDrawStatus;
use App\Models\LotteryDraw;
use App\Models\User;
use App\Services\Dashboard\Timeline\TimelineEventFactory;
use App\Services\Dashboard\Timeline\BaseTimelineProvider;
use Illuminate\Support\Collection;

class LotteryTimelineProvider extends BaseTimelineProvider
{
    public function __construct(
        private readonly TimelineEventFactory $factory = new TimelineEventFactory(),
    ) {}

    public function forUser(User $user, array $dashboard = []): array
    {
        if (! $user->hasPermission('allocations.view')) {
            return [];
        }

        return LotteryDraw::query()
            ->with(['contest', 'program'])
            ->whereIn('status', [
                LotteryDrawStatus::Ready->value,
                LotteryDrawStatus::Running->value,
                LotteryDrawStatus::Completed->value,
                LotteryDrawStatus::Validated->value,
                LotteryDrawStatus::ParticipantsLocked->value,
            ])
            ->where(function ($query): void {
                $query
                    ->whereNotNull('scheduled_at')
                    ->orWhereNotNull('completed_at')
                    ->orWhereNotNull('validated_at');
            })
            ->orderByRaw('COALESCE(scheduled_at, completed_at, validated_at, created_at) asc')
            ->limit(30)
            ->get()
            ->flatMap(fn (LotteryDraw $draw): Collection => $this->eventsForDraw($draw))
            ->values()
            ->all();
    }

    /** @return Collection<int, TimelineEvent> */
    private function eventsForDraw(LotteryDraw $draw): Collection
    {
        return collect()
            ->when($draw->scheduled_at, fn (Collection $events): Collection => $events->push($this->scheduledEvent($draw)))
            ->when(
                in_array($draw->status, [LotteryDrawStatus::Ready, LotteryDrawStatus::ParticipantsLocked, LotteryDrawStatus::Running], true),
                fn (Collection $events): Collection => $events->push($this->readyEvent($draw))
            )
            ->when($draw->completed_at, fn (Collection $events): Collection => $events->push($this->completedEvent($draw)))
            ->when($draw->validated_at, fn (Collection $events): Collection => $events->push($this->validatedEvent($draw)));
    }

    private function scheduledEvent(LotteryDraw $draw): TimelineEvent
    {
        return $this->factory->make(
            id: 'lottery-scheduled-'.$draw->getKey(),
            type: TimelineType::LotteryScheduled,
            title: 'Sorteio agendado',
            description: $this->description($draw),
            route: route('backoffice.lottery-draws.show', $draw),
            datetime: $draw->scheduled_at,
            priority: $draw->scheduled_at?->isPast() ? TimelinePriority::High : TimelinePriority::Medium,
            icon: 'calendar',
            tone: $draw->scheduled_at?->isPast() ? 'warning' : 'info',
            workspace: TimelineWorkspace::Contests,
            metadata: $this->metadata($draw),
        );
    }

    private function readyEvent(LotteryDraw $draw): TimelineEvent
    {
        return $this->factory->make(
            id: 'lottery-ready-'.$draw->getKey(),
            type: TimelineType::LotteryReady,
            title: 'Sorteio pronto para execução',
            description: $this->description($draw),
            route: route('backoffice.lottery-draws.show', $draw),
            datetime: $draw->scheduled_at ?? $draw->updated_at,
            priority: TimelinePriority::High,
            icon: 'shuffle',
            tone: 'warning',
            workspace: TimelineWorkspace::Contests,
            metadata: $this->metadata($draw),
        );
    }

    private function completedEvent(LotteryDraw $draw): TimelineEvent
    {
        return $this->factory->make(
            id: 'lottery-completed-'.$draw->getKey(),
            type: TimelineType::LotteryCompleted,
            title: 'Sorteio concluído',
            description: $this->description($draw),
            route: route('backoffice.lottery-draws.show', $draw),
            datetime: $draw->completed_at,
            priority: TimelinePriority::Medium,
            icon: 'check-circle',
            tone: 'success',
            workspace: TimelineWorkspace::Contests,
            metadata: $this->metadata($draw),
        );
    }

    private function validatedEvent(LotteryDraw $draw): TimelineEvent
    {
        return $this->factory->make(
            id: 'lottery-validated-'.$draw->getKey(),
            type: TimelineType::LotteryValidated,
            title: 'Sorteio validado',
            description: $this->description($draw),
            route: route('backoffice.lottery-draws.show', $draw),
            datetime: $draw->validated_at,
            priority: TimelinePriority::Low,
            icon: 'shield-check',
            tone: 'success',
            workspace: TimelineWorkspace::Contests,
            metadata: $this->metadata($draw),
        );
    }

    private function description(LotteryDraw $draw): string
    {
        $contest = $draw->contest?->title ?? 'Concurso';
        $program = $draw->program?->title ?? 'Programa';

        return trim("{$contest} · {$program}");
    }

    /** @return array<string, mixed> */
    private function metadata(LotteryDraw $draw): array
    {
        return [
            'lottery_draw_id' => $draw->getKey(),
            'contest_id' => $draw->contest_id,
            'contest_title' => $draw->contest?->title,
            'program_id' => $draw->program_id,
            'program_title' => $draw->program?->title,
            'status' => $draw->status?->value ?? $draw->status,
            'draw_type' => $draw->draw_type?->value ?? $draw->draw_type,
            'scheduled_at' => $draw->scheduled_at?->toIso8601String(),
            'completed_at' => $draw->completed_at?->toIso8601String(),
            'validated_at' => $draw->validated_at?->toIso8601String(),
        ];
    }
}
