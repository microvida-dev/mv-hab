<?php

namespace App\Services\Security;

use App\Enums\AccessLogType;
use App\Enums\SecurityAlertStatus;
use App\Models\AccessLog;
use App\Models\SecurityAlert;
use App\Models\SecurityAlertRule;
use App\Models\SensitiveDataAccessLog;
use App\Models\User;
use Illuminate\Support\Str;

class SecurityAlertService
{
    public function __construct(private readonly SecurityAlertRuleEvaluator $evaluator) {}

    public function evaluateAccess(AccessLog $log): ?SecurityAlert
    {
        if ($log->access_type !== AccessLogType::FailedLogin) {
            return null;
        }

        $rule = SecurityAlertRule::query()->where('code', 'multiple_failed_logins')->where('is_active', true)->first();
        $user = $log->user instanceof User ? $log->user : null;

        return $rule && $this->evaluator->thresholdReached($rule, $log)
            ? $this->create($rule, $user, 'Múltiplas falhas de login', 'Foram detetadas múltiplas falhas de login dentro da janela configurada.', ['ip_address' => $log->ip_address])
            : null;
    }

    public function evaluateSensitiveAccess(SensitiveDataAccessLog $log): ?SecurityAlert
    {
        $code = match ($log->action) {
            'download' => 'sensitive_document_bulk_download',
            'export' => 'sensitive_report_bulk_export',
            default => 'access_to_multiple_candidate_records',
        };

        $rule = SecurityAlertRule::query()->where('code', $code)->where('is_active', true)->first();
        $user = $log->user instanceof User ? $log->user : null;

        return $rule && $this->evaluator->thresholdReached($rule, $log)
            ? $this->create($rule, $user, $rule->name, $rule->description, ['resource_type' => $log->resource_type, 'action' => $log->action])
            : null;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function create(SecurityAlertRule $rule, ?User $user, string $title, ?string $description = null, array $metadata = []): SecurityAlert
    {
        return SecurityAlert::query()->create([
            'alert_number' => 'SEC-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6)),
            'security_alert_rule_id' => $rule->id,
            'user_id' => $user?->id,
            'status' => SecurityAlertStatus::Open,
            'severity' => $rule->severity,
            'title' => $title,
            'description' => $description,
            'detected_at' => now(),
            'metadata' => $metadata,
        ]);
    }

    public function review(SecurityAlert $alert, User $actor): SecurityAlert
    {
        $alert->forceFill(['status' => SecurityAlertStatus::UnderReview, 'reviewed_by' => $actor->id, 'reviewed_at' => now()])->save();

        return $alert->refresh();
    }

    public function resolve(SecurityAlert $alert, User $actor, string $notes, bool $falsePositive = false): SecurityAlert
    {
        $alert->forceFill([
            'status' => $falsePositive ? SecurityAlertStatus::FalsePositive : SecurityAlertStatus::Resolved,
            'resolved_by' => $actor->id,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ])->save();

        return $alert->refresh();
    }
}
