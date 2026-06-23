<?php

namespace App\Services\AdministrativeDecision;

use App\Enums\DefinitiveListStatus;
use App\Models\DefinitiveList;
use App\Models\DefinitiveListEntry;
use Illuminate\Database\Eloquent\Builder;

class FinalDecisionReadinessService
{
    /** @return Builder<DefinitiveListEntry> */
    public function readyEntriesQuery(?DefinitiveList $list = null): Builder
    {
        $query = DefinitiveListEntry::query()->eligibleForAllocation();

        if ($list !== null) {
            $query->where('definitive_list_id', $list->id);
        }

        return $query;
    }

    public function isReady(DefinitiveList $list): bool
    {
        return $this->definitiveListStatusIsIn($list, [DefinitiveListStatus::Published, DefinitiveListStatus::Locked])
            && DefinitiveListEntry::query()
                ->where('definitive_list_id', $list->id)
                ->eligibleForAllocation()
                ->exists();
    }

    /**
     * @param  list<DefinitiveListStatus>  $statuses
     */
    private function definitiveListStatusIsIn(DefinitiveList $list, array $statuses): bool
    {
        $status = $list->getAttribute('status');

        if (is_string($status)) {
            $status = DefinitiveListStatus::tryFrom($status);
        }

        return $status instanceof DefinitiveListStatus && in_array($status, $statuses, true);
    }
}
