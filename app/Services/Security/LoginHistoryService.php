<?php

namespace App\Services\Security;

use App\Enums\AccessLogType;
use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Models\AccessLog;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use Illuminate\Support\Str;

class LoginHistoryService
{
    public function __construct(
        private readonly AccessLogService $access,
        private readonly AuditTrailService $audit,
        private readonly SecurityAlertService $alerts,
    ) {}

    public function recordSuccess(User $user): AccessLog
    {
        $log = $this->access->record(AccessLogType::Login, $user, $user, 200);

        $user->forceFill(['last_login_at' => now()])->save();

        $this->audit->record(
            'login_success',
            $user,
            AuditEventCategory::Security,
            AuditEventSeverity::Notice,
            'Login autenticado com sucesso.',
            actor: $user,
        );

        return $log;
    }

    public function recordFailed(string $email): AccessLog
    {
        $log = $this->access->record(AccessLogType::FailedLogin, metadata: [
            'email_hash' => hash('sha256', Str::lower($email)),
        ]);

        $this->audit->record(
            'login_failed',
            category: AuditEventCategory::Security,
            severity: AuditEventSeverity::Warning,
            description: 'Falha de autenticação registada.',
            metadata: [
                'email_hash' => hash('sha256', Str::lower($email)),
            ],
            useAuthenticatedUser: false,
        );

        $this->alerts->evaluateAccess($log);

        return $log;
    }

    public function recordLogout(User $user): AccessLog
    {
        $log = $this->access->record(AccessLogType::Logout, $user, $user, 200);

        $this->audit->record(
            'logout',
            $user,
            AuditEventCategory::Security,
            AuditEventSeverity::Notice,
            'Logout registado.',
            actor: $user,
        );

        return $log;
    }
}
