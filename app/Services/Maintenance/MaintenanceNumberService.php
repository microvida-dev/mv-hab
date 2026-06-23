<?php

namespace App\Services\Maintenance;

use App\Models\MaintenanceRequest;
use App\Models\PropertyInspection;
use App\Models\PropertyInspectionReport;

class MaintenanceNumberService
{
    public function requestNumber(): string
    {
        return $this->next('MAN', MaintenanceRequest::class, 'request_number');
    }

    public function inspectionNumber(): string
    {
        return $this->next('VIS', PropertyInspection::class, 'inspection_number');
    }

    public function inspectionReportNumber(): string
    {
        return $this->next('AUTO', PropertyInspectionReport::class, 'report_number');
    }

    private function next(string $prefix, string $model, string $column): string
    {
        do {
            $number = sprintf('%s-%s-%05d', $prefix, now()->format('Y'), random_int(1, 99999));
        } while ($model::query()->where($column, $number)->exists());

        return $number;
    }
}
