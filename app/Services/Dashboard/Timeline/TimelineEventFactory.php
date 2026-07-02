<?php

declare(strict_types=1);

namespace App\Services\Dashboard\Timeline;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use Illuminate\Support\Carbon;

final class TimelineEventFactory
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function make(
        string $id,
        TimelineType $type,
        string $title,
        string $description,
        string $route,
        Carbon|string|null $datetime,
        TimelinePriority $priority,
        string $icon,
        string $tone,
        TimelineWorkspace $workspace,
        array $metadata = [],
    ): TimelineEvent {
        return new TimelineEvent(
            id: $id,
            type: $type,
            title: $title,
            description: $description,
            route: $route,
            datetime: $datetime instanceof Carbon ? $datetime : ($datetime ? Carbon::parse($datetime) : null),
            priority: $priority,
            icon: $icon,
            tone: $tone,
            workspace: $workspace,
            metadata: $metadata,
        );
    }
}
