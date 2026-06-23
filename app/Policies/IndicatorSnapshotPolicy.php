<?php

namespace App\Policies;

use App\Models\IndicatorSnapshot;
use App\Models\User;

class IndicatorSnapshotPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermission('reports.view');
    }

    public function view(User $user, IndicatorSnapshot $snapshot): bool
    {
        return $user->can('view', $snapshot->definition);
    }
}
