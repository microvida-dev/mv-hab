<?php

namespace App\Services\Reporting;

use App\Models\ReportDefinition;
use App\Models\User;

class ReportDefinitionService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): ReportDefinition
    {
        $report = new ReportDefinition;
        $report->forceFill($data + ['created_by' => $user->getKey(), 'updated_by' => $user->getKey()]);
        $report->save();

        return $report;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ReportDefinition $report, array $data, User $user): ReportDefinition
    {
        $report->forceFill($data + ['updated_by' => $user->getKey()])->save();

        return $report->refresh();
    }
}
