<?php

namespace App\Data\Dashboard;

use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineStatus;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use Illuminate\Support\Carbon;

final readonly class TimelineEvent
{
    public function __construct(
        public string $id,
        public TimelineType $type,
        public string $title,
        public ?string $description = null,
        public ?string $route = null,
        public ?Carbon $datetime = null,
        public TimelinePriority $priority = TimelinePriority::Medium,
        public TimelineStatus $status = TimelineStatus::Pending,
        public string $icon = 'calendar',
        public string $tone = 'neutral',
        public ?TimelineWorkspace $workspace = null,
        public array $metadata = [],
    ) {}

    public function priorityWeight(): int
    {
        return $this->priority->weight();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'priority' => $this->priority->value,
            'priority_weight' => $this->priorityWeight(),
            'status' => $this->status->value,
            'datetime' => $this->datetime?->toIso8601String(),
            'date' => $this->datetime?->toDateString(),
            'time' => $this->datetime?->format('H:i'),
            'title' => $this->title,
            'description' => $this->description,
            'route' => $this->route,
            'icon' => $this->icon,
            'tone' => $this->tone,
            'workspace' => $this->workspace?->value,
            'metadata' => $this->metadata,
        ];
    }
}
