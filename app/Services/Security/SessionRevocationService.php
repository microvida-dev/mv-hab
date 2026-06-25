<?php

namespace App\Services\Security;

use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SessionRevocationService
{
    public function __construct(private readonly AuditTrailService $audit) {}

    public function revokeAllForUser(User $target, ?User $actor, string $reason, bool $includeCurrentSession = true): int
    {
        if (! Schema::hasTable('sessions')) {
            return 0;
        }

        $query = DB::table('sessions')->where('user_id', $target->id);
        $request = request();
        $currentSessionId = $request->hasSession() ? $request->session()->getId() : null;

        if (! $includeCurrentSession && is_string($currentSessionId)) {
            $query->where('id', '!=', $currentSessionId);
        }

        $count = $query->count();
        $query->delete();

        $this->audit->record(
            'all_sessions_revoked',
            $target,
            AuditEventCategory::Security,
            AuditEventSeverity::Security,
            $reason,
            metadata: [
                'revoked_sessions_count' => $count,
                'include_current_session' => $includeCurrentSession,
            ],
            subject: $target,
            actor: $actor,
        );

        if ($count > 0) {
            $this->audit->record(
                'session_revoked',
                $target,
                AuditEventCategory::Security,
                AuditEventSeverity::Security,
                'Sessões remotas revogadas.',
                metadata: [
                    'revoked_sessions_count' => $count,
                ],
                subject: $target,
                actor: $actor,
            );
        }

        return $count;
    }
}
