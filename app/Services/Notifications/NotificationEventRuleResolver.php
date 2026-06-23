<?php

namespace App\Services\Notifications;

use App\Models\NotificationEventRule;
use Illuminate\Support\Collection;

class NotificationEventRuleResolver
{
    /**
     * @param  array<string, mixed>  $context
     * @return Collection<int, NotificationEventRule>
     */
    public function resolve(string $eventCode, array $context = []): Collection
    {
        return NotificationEventRule::query()
            ->with(['template.activeVersion'])
            ->where('event_code', $eventCode)
            ->where('is_active', true)
            ->get()
            ->filter(function (NotificationEventRule $rule) use ($context) {
                return (! $rule->contest_id || $rule->contest_id === ($context['contest_id'] ?? null))
                    && (! $rule->program_id || $rule->program_id === ($context['program_id'] ?? null))
                    && (! $rule->municipality_id || $rule->municipality_id === ($context['municipality_id'] ?? null));
            })
            ->sortByDesc(fn (NotificationEventRule $rule) => ($rule->contest_id ? 100 : 0) + ($rule->program_id ? 10 : 0) + ($rule->municipality_id ? 1 : 0))
            ->groupBy(fn (NotificationEventRule $rule) => $rule->recipient_type.'|'.$rule->channel->value)
            ->map->first()
            ->filter(fn (mixed $rule): bool => $rule instanceof NotificationEventRule)
            ->values();
    }
}
