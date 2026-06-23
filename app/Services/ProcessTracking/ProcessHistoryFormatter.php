<?php

namespace App\Services\ProcessTracking;

use Illuminate\Support\Collection;

/**
 * @phpstan-type TimelineEvent array{date: mixed, type: string, title: string, description: string|null, visibility: string, due_at: mixed, public_status: string|null}
 */
class ProcessHistoryFormatter
{
    /**
     * @param  Collection<int, TimelineEvent>  $events
     * @return Collection<string, Collection<int, TimelineEvent>>
     */
    public function groupByPhase(Collection $events): Collection
    {
        return $events->groupBy(function (array $event): string {
            $type = $event['type'];

            if (str_contains($type, 'document') || str_contains($type, 'correction')) {
                return 'Documentos e aperfeiçoamentos';
            }

            if (str_contains($type, 'hearing') || str_contains($type, 'complaint')) {
                return 'Audiência e reclamações';
            }

            if (str_contains($type, 'notification')) {
                return 'Comunicações';
            }

            return 'Processo';
        });
    }
}
