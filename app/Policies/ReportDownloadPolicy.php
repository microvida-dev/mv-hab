<?php

namespace App\Policies;

use App\Models\ReportExport;
use App\Models\User;

class ReportDownloadPolicy
{
    public function download(User $user, ReportExport $export): bool
    {
        return $user->can('download', $export);
    }
}
