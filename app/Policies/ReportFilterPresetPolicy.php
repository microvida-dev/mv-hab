<?php

namespace App\Policies;

use App\Models\ReportFilterPreset;
use App\Models\User;

class ReportFilterPresetPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermission('reports.view');
    }

    public function view(User $user, ReportFilterPreset $preset): bool
    {
        return $preset->user_id === $user->getKey();
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, ReportFilterPreset $preset): bool
    {
        return $this->view($user, $preset);
    }

    public function delete(User $user, ReportFilterPreset $preset): bool
    {
        return $this->view($user, $preset);
    }
}
