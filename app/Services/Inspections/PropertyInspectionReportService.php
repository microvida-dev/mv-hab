<?php

namespace App\Services\Inspections;

use App\Enums\InspectionReportStatus;
use App\Enums\OfficialNotificationType;
use App\Enums\TechnicalHistoryEventType;
use App\Models\HousingUnit;
use App\Models\PropertyInspection;
use App\Models\PropertyInspectionReport;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Maintenance\MaintenanceNotificationService;
use App\Services\Maintenance\MaintenanceNumberService;
use App\Services\Properties\PropertyTechnicalHistoryService;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PropertyInspectionReportService
{
    public function __construct(
        private readonly MaintenanceNumberService $numbers,
        private readonly AuditLogger $auditLogger,
        private readonly PropertyTechnicalHistoryService $history,
        private readonly MaintenanceNotificationService $notifications,
    ) {}

    public function generate(PropertyInspection $inspection, User $actor): PropertyInspectionReport
    {
        $inspection->load(['housingUnit', 'leaseContract', 'inspector', 'items', 'attachments']);
        $report = $inspection->report ?: new PropertyInspectionReport(['property_inspection_id' => $inspection->id]);
        $reportNumber = $report->report_number ?: $this->numbers->inspectionReportNumber();
        $html = view('inspections.reports.property-inspection-report', compact('inspection', 'reportNumber'))->render();
        $path = 'inspections/reports/'.$reportNumber.'.html';

        Storage::disk('local')->put($path, $html);

        $report->forceFill([
            'report_number' => $reportNumber,
            'status' => InspectionReportStatus::Generated,
            'storage_disk' => 'local',
            'storage_path' => $path,
            'mime_type' => 'text/html',
            'checksum' => hash('sha256', $html),
            'generated_at' => now(),
            'generated_by' => $actor->id,
        ])->save();

        $this->auditLogger->record(AuditEvents::CREATE, $report, 'inspections', 'inspection_report_generated', 'Auto de vistoria gerado.');
        $this->history->record($this->housingUnitForInspection($inspection), TechnicalHistoryEventType::InspectionReportIssued, 'Auto de vistoria gerado', $report->report_number, $actor, $inspection->leaseContract, inspection: $inspection, report: $report, visibleToTenant: true);
        $this->notifications->inspectionStatus($inspection, OfficialNotificationType::InspectionReportAvailable, 'Auto de vistoria disponível', 'O auto de vistoria está disponível na área reservada.', $actor);

        return $report->refresh();
    }

    public function validate(PropertyInspectionReport $report, User $actor): PropertyInspectionReport
    {
        $report->forceFill([
            'status' => InspectionReportStatus::Validated,
            'validated_at' => now(),
            'validated_by' => $actor->id,
            'issued_at' => now(),
        ])->save();

        $report->inspection()->update(['tenant_visible' => true]);

        $this->auditLogger->record(AuditEvents::APPROVE, $report, 'inspections', 'inspection_report_validated', 'Auto de vistoria validado.');

        return $report->refresh();
    }

    public function cancel(PropertyInspectionReport $report, User $actor, ?string $reason = null): PropertyInspectionReport
    {
        $report->forceFill([
            'status' => InspectionReportStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ])->save();

        return $report->refresh();
    }

    public function download(PropertyInspectionReport $report, User $actor): StreamedResponse
    {
        abort_unless($report->storage_disk && $report->storage_path, 404);
        abort_unless(Storage::disk($report->storage_disk)->exists($report->storage_path), 404);

        $this->auditLogger->record(AuditEvents::ACCESS, $report, 'inspections', 'inspection_report_download', 'Auto de vistoria descarregado.');

        return Storage::disk($report->storage_disk)->download($report->storage_path, $report->report_number.'.html');
    }

    private function housingUnitForInspection(PropertyInspection $inspection): HousingUnit
    {
        $housingUnit = $inspection->housingUnit;

        if (! $housingUnit instanceof HousingUnit) {
            throw ValidationException::withMessages([
                'housing_unit' => 'A vistoria não tem fogo associado.',
            ]);
        }

        return $housingUnit;
    }
}
