<?php

namespace App\Services\Security;

use App\Models\AccessLog;
use App\Models\SecurityAlertRule;
use App\Models\SensitiveDataAccessLog;

class SecurityAlertRuleEvaluator
{
    public function thresholdReached(SecurityAlertRule $rule, AccessLog|SensitiveDataAccessLog $event): bool
    {
        $threshold = $rule->threshold ?: 1;
        $window = now()->subMinutes($rule->window_minutes ?: 60);

        if ($event instanceof AccessLog) {
            return AccessLog::query()
                ->where('access_type', $event->access_type)
                ->where('ip_address', $event->ip_address)
                ->where('accessed_at', '>=', $window)
                ->count() >= $threshold;
        }

        return SensitiveDataAccessLog::query()
            ->where('user_id', $event->user_id)
            ->where('action', $event->action)
            ->where('accessed_at', '>=', $window)
            ->count() >= $threshold;
    }
}
