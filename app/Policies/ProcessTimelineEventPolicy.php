<?php

namespace App\Policies;

use App\Enums\TimelineEventVisibility;
use App\Models\ProcessTimelineEvent;
use App\Models\User;

class ProcessTimelineEventPolicy
{
    public function view(User $user, ProcessTimelineEvent $event): bool
    {
        if ($user->hasPermissionTo('audit', 'view') || $user->hasPermissionTo('applications', 'view')) {
            return true;
        }

        return $event->user_id === $user->id && $this->visibility($event) === TimelineEventVisibility::CandidateVisible;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('applications', 'update') || $user->hasPermissionTo('audit', 'create');
    }

    private function visibility(ProcessTimelineEvent $event): ?TimelineEventVisibility
    {
        $visibility = $event->getAttribute('visibility');

        if ($visibility instanceof TimelineEventVisibility) {
            return $visibility;
        }

        return is_string($visibility) ? TimelineEventVisibility::tryFrom($visibility) : null;
    }
}
