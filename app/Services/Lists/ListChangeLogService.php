<?php

namespace App\Services\Lists;

use App\Enums\ListChangeType;
use App\Models\Application;
use App\Models\DefinitiveList;
use App\Models\ListChangeLog;
use App\Models\ProvisionalList;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ListChangeLogService
{
    public function record(
        ListChangeType $type,
        Application $application,
        ?ProvisionalList $provisionalList = null,
        ?DefinitiveList $definitiveList = null,
        ?User $actor = null,
        ?Model $source = null,
        ?string $from = null,
        ?string $to = null,
        ?string $reason = null,
    ): ListChangeLog {
        $log = new ListChangeLog;
        $log->forceFill([
            'provisional_list_id' => $provisionalList?->id,
            'definitive_list_id' => $definitiveList?->id,
            'application_id' => $application->id,
            'user_id' => $application->user_id,
            'change_type' => $type,
            'from_value' => $from,
            'to_value' => $to,
            'reason' => $reason,
            'source_type' => $source?->getMorphClass(),
            'source_id' => $source?->getKey(),
            'changed_by' => $actor?->id,
            'created_at' => now(),
        ])->save();

        return $log;
    }
}
