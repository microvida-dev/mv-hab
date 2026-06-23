<?php

namespace App\Policies;

use App\Models\PropertyInspectionReport;
use App\Models\User;
use App\Policies\Concerns\ChecksMaintenanceAccess;

class PropertyInspectionReportPolicy
{
    use ChecksMaintenanceAccess;

    public function view(User $user, PropertyInspectionReport $report): bool
    {
        return $user->can('view', $report->inspection);
    }

    public function generate(User $user, PropertyInspectionReport|string|null $report = null): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $user->hasPermissionTo('inspections', 'update');
    }

    public function approve(User $user, PropertyInspectionReport $report): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $user->hasPermissionTo('inspections', 'approve');
    }

    public function download(User $user, PropertyInspectionReport $report): bool
    {
        return $this->view($user, $report);
    }
}
