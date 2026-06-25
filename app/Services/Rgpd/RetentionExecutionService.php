<?php

namespace App\Services\Rgpd;

use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Enums\RetentionExecutionStatus;
use App\Models\RetentionExecution;
use App\Models\RetentionPolicy;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RuntimeException;

class RetentionExecutionService
{
    public function __construct(private readonly AuditTrailService $audit) {}

    public function simulate(RetentionPolicy $policy, User $actor): RetentionExecution
    {
        $matched = $this->countRecords($policy->entity_type);

        $execution = RetentionExecution::query()->create([
            'execution_number' => 'RET-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5)),
            'retention_policy_id' => $policy->id,
            'status' => RetentionExecutionStatus::Simulation,
            'mode' => 'simulation',
            'matched_records_count' => $matched,
            'affected_records_count' => 0,
            'started_by' => $actor->id,
            'started_at' => now(),
            'completed_at' => now(),
            'summary' => ['message' => 'Simulação sem alteração de dados.', 'action' => $policy->retention_action->value],
        ]);

        $this->audit->record('retention.simulated', $execution, AuditEventCategory::Rgpd, AuditEventSeverity::Notice, 'Simulação de retenção executada.', actor: $actor);
        $this->audit->record('rgpd_retention_simulated', $execution, AuditEventCategory::Rgpd, AuditEventSeverity::Notice, 'Simulação RGPD de retenção executada.', actor: $actor);

        return $execution;
    }

    public function approve(RetentionExecution $execution, User $actor): RetentionExecution
    {
        $execution->forceFill(['status' => RetentionExecutionStatus::Approved, 'approved_by' => $actor->id])->save();
        $this->audit->record('rgpd_retention_approved', $execution, AuditEventCategory::Rgpd, AuditEventSeverity::Warning, 'Execução de retenção aprovada.', actor: $actor);

        return $execution->refresh();
    }

    public function run(RetentionExecution $execution, User $actor): RetentionExecution
    {
        $execution->loadMissing('policy');
        $policy = $execution->policy;

        if (! $policy instanceof RetentionPolicy) {
            throw new RuntimeException('Execução de retenção sem política associada.');
        }

        if ($policy->requires_manual_approval && $execution->status !== RetentionExecutionStatus::Approved) {
            throw new RuntimeException('Execução real de retenção exige aprovação prévia.');
        }

        $execution->forceFill([
            'status' => RetentionExecutionStatus::Completed,
            'mode' => 'real',
            'started_by' => $execution->started_by ?: $actor->id,
            'started_at' => $execution->started_at ?: now(),
            'completed_at' => now(),
            'affected_records_count' => 0,
            'summary' => ['message' => 'Execução real conservadora: nenhuma eliminação permanente foi efetuada nesta sprint.'],
        ])->save();

        $this->audit->record('retention.executed', $execution, AuditEventCategory::Rgpd, AuditEventSeverity::Warning, 'Execução de retenção registada.', actor: $actor);
        $this->audit->record('rgpd_retention_executed', $execution, AuditEventCategory::Rgpd, AuditEventSeverity::Warning, 'Execução RGPD de retenção registada.', actor: $actor);

        return $execution->refresh();
    }

    private function countRecords(string $class): int
    {
        return is_subclass_of($class, Model::class) ? $class::query()->count() : 0;
    }
}
