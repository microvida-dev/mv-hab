<?php

namespace App\Services\ProcessTracking;

use App\Enums\PublicProcessStatus;
use App\Enums\TimelineEventType;
use App\Enums\TimelineEventVisibility;
use App\Models\Application;
use App\Models\ProcessTimelineEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ProcessTimelineService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        Application $application,
        TimelineEventType $type,
        TimelineEventVisibility $visibility,
        string $title,
        ?string $description = null,
        ?User $actor = null,
        ?Model $related = null,
        ?PublicProcessStatus $publicStatus = null,
        ?Carbon $dueAt = null,
        array $metadata = [],
    ): ProcessTimelineEvent {
        $event = new ProcessTimelineEvent([
            'event_type' => $type,
            'visibility' => $visibility,
            'public_status' => $publicStatus,
            'title' => $title,
            'description' => $description,
            'occurred_at' => now(),
            'due_at' => $dueAt,
            'metadata' => $metadata ?: null,
        ]);

        $event->forceFill([
            'event_number' => $this->number(),
            'user_id' => $application->user_id,
            'application_id' => $application->id,
            'adhesion_registration_id' => $application->adhesion_registration_id,
            'contest_id' => $application->contest_id,
            'created_by' => $actor?->id,
            'related_type' => $related?->getMorphClass(),
            'related_id' => $related?->getKey(),
        ])->save();

        return $event->refresh();
    }

    private function number(): string
    {
        do {
            $number = 'TL-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
        } while (ProcessTimelineEvent::query()->where('event_number', $number)->exists());

        return $number;
    }
}
