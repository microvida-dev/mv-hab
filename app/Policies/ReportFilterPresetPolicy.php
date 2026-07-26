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

    public function viewAnyBackoffice(User $user): bool
    {
        return $user->hasPermission('report_filter_presets.view');
    }

    public function createBackoffice(User $user): bool
    {
        return $user->hasPermission('report_filter_presets.create');
    }

    public function updateBackoffice(
        User $user,
        ReportFilterPreset $preset,
    ): bool {
        return $user->hasPermission('report_filter_presets.update')
            && $preset->user_id === $user->getKey();
    }

    public function deleteBackoffice(
        User $user,
        ReportFilterPreset $preset,
    ): bool {
        return $user->hasPermission('report_filter_presets.delete')
            && $preset->user_id === $user->getKey();
    }
}
