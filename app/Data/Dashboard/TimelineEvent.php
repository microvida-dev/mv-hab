<?php

namespace App\Data\Dashboard;

use Illuminate\Support\Carbon;

final readonly class TimelineEvent
{
    public function __construct(
        public string $id,
        public string $type,
        public string $title,
        public ?string $description = null,
        public ?string $route = null,
        public ?Carbon $datetime = null,
        public string $priority = 'medium',
        public string $status = 'pending',
        public string $icon = 'calendar',
        public string $tone = 'neutral',
        public ?string $workspace = null,
        public array $metadata = [],
    ) {}

    public function priorityWeight(): int
    {
        return match ($this->priority) {
            'critical' => 10,
            'high' => 20,
            'medium' => 40,
            'low' => 60,
            default => 80,
        };
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'priority' => $this->priority,
            'priority_weight' => $this->priorityWeight(),
            'status' => $this->status,
            'datetime' => $this->datetime?->toIso8601String(),
            'date' => $this->datetime?->toDateString(),
            'time' => $this->datetime?->format('H:i'),
            'title' => $this->title,
            'description' => $this->description,
            'route' => $this->route,
            'icon' => $this->icon,
            'tone' => $this->tone,
            'workspace' => $this->workspace,
            'metadata' => $this->metadata,
        ];
    }
}
