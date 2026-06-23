<?php

namespace App\Services\Lists;

use App\Models\DefinitiveList;
use App\Models\ProvisionalList;

class ListVersionService
{
    public function nextProvisionalVersion(ProvisionalList $list): int
    {
        return ProvisionalList::withTrashed()
            ->where('contest_id', $list->contest_id)
            ->where('ranking_snapshot_id', $list->ranking_snapshot_id)
            ->max('version_number') + 1;
    }

    public function nextDefinitiveVersion(DefinitiveList $list): int
    {
        return DefinitiveList::withTrashed()
            ->where('provisional_list_id', $list->provisional_list_id)
            ->max('version_number') + 1;
    }
}
