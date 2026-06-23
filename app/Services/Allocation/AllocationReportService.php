<?php

namespace App\Services\Allocation;

use App\Enums\AllocationMethod;
use App\Enums\AllocationReportStatus;
use App\Enums\AllocationRunStatus;
use App\Models\AllocationReport;
use App\Models\AllocationRun;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;

class AllocationReportService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function generate(AllocationRun $run, User $actor, ?string $legalBasis = null): AllocationReport
    {
        $run->loadMissing(['allocations', 'reserveList.entries', 'lotteryRun']);

        $report = new AllocationReport([
            'allocation_run_id' => $run->id,
            'program_id' => $run->program_id,
            'contest_id' => $run->contest_id,
            'definitive_list_id' => $run->definitive_list_id,
            'title' => 'Ata de atribuição '.$run->run_number,
            'summary' => 'Relatório preliminar da execução de atribuição.',
            'method_description' => $this->allocationMethodLabel($run),
            'legal_basis' => $legalBasis,
            'results_summary' => [
                'allocations' => $run->allocations()->count(),
                'accepted' => $run->allocations()->whereIn('status', ['accepted', 'ready_for_contract'])->count(),
                'offered' => $run->allocations()->where('status', 'offered')->count(),
            ],
            'exceptions_summary' => [
                'failed' => $this->allocationRunStatus($run) === AllocationRunStatus::Failed,
                'failure_reason' => $run->failure_reason,
            ],
            'refusals_summary' => [
                'refusals' => $run->allocations()->where('status', 'refused')->count(),
            ],
            'reserve_summary' => [
                'reserve_entries' => $run->reserveList?->entries()->count() ?? 0,
            ],
        ]);
        $report->forceFill([
            'report_number' => $this->generateReportNumber(),
            'status' => AllocationReportStatus::Generated,
            'generated_by' => $actor->id,
            'generated_at' => now(),
        ])->save();

        $this->auditLogger->record(AuditEvents::CREATE, $report, 'allocations', 'allocation_report_generate', 'Relatório de atribuição gerado.');

        return $report->refresh();
    }

    public function approve(AllocationReport $report, User $actor): AllocationReport
    {
        $report->forceFill([
            'status' => AllocationReportStatus::Approved,
            'approved_by' => $actor->id,
            'approved_at' => now(),
        ])->save();
        $this->auditLogger->record(AuditEvents::APPROVE, $report, 'allocations', 'allocation_report_approve', 'Relatório de atribuição aprovado.');

        return $report->refresh();
    }

    private function generateReportNumber(): string
    {
        $next = AllocationReport::withTrashed()->count() + 1;

        do {
            $number = 'RA-'.now()->format('Y').'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (AllocationReport::withTrashed()->where('report_number', $number)->exists());

        return $number;
    }

    private function allocationMethodLabel(AllocationRun $run): string
    {
        $method = $run->getAttribute('allocation_method');

        if ($method instanceof AllocationMethod) {
            return $method->label();
        }

        if (is_string($method)) {
            return AllocationMethod::tryFrom($method)?->label() ?? $method;
        }

        return 'N/D';
    }

    private function allocationRunStatus(AllocationRun $run): ?AllocationRunStatus
    {
        $status = $run->getAttribute('status');

        if ($status instanceof AllocationRunStatus) {
            return $status;
        }

        return is_string($status) ? AllocationRunStatus::tryFrom($status) : null;
    }
}
