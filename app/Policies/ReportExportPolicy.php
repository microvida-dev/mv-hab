<?php

namespace App\Policies;

use App\Models\ReportExport;
use App\Models\User;
use App\Services\Reporting\ReportPermissionService;

class ReportExportPolicy
{
    public function __construct(private readonly ReportPermissionService $permissions) {}

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermission('reports.view');
    }

    public function view(User $user, ReportExport $export): bool
    {
        return $this->permissions->canViewReport($user, $export->run->definition);
    }

    public function download(User $user, ReportExport $export): bool
    {
        return $this->permissions->canExport($user, $export->run->definition, $export->scope);
    }
}
