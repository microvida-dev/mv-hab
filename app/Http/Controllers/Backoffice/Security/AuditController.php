<?php

namespace App\Http\Controllers\Backoffice\Security;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Models\AuditEvent;
use App\Models\SensitiveDataAccessLog;
use App\Services\Security\SecurityMunicipalScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuditController extends Controller
{
    public function __construct(
        private readonly SecurityMunicipalScopeService $scope,
    ) {}

    public function events(Request $request): View
    {
        $actor = $this->authenticatedUser($request);
        Gate::authorize('viewAny', AuditEvent::class);

        return view('backoffice.security.audit.events', [
            'events' => $this->scope
                ->auditEvents(AuditEvent::query(), $actor)
                ->with('user', 'subjectUser')
                ->latest('occurred_at')
                ->paginate(25),
        ]);
    }

    public function event(Request $request, AuditEvent $auditEvent): View
    {
        Gate::authorize('view', $auditEvent);

        return view('backoffice.security.audit.event', ['event' => $auditEvent->load('user', 'subjectUser')]);
    }

    public function accessLogs(Request $request): View
    {
        $actor = $this->authenticatedUser($request);
        abort_unless($actor->hasPermission('security.view_access_logs'), 403);

        return view('backoffice.security.audit.access-logs', [
            'logs' => $this->scope
                ->accessLogs(AccessLog::query(), $actor)
                ->with('user')
                ->latest('accessed_at')
                ->paginate(25),
        ]);
    }

    public function sensitiveLogs(Request $request): View
    {
        $actor = $this->authenticatedUser($request);
        abort_unless($actor->hasPermission('security.audit_sensitive_access'), 403);

        return view('backoffice.security.audit.sensitive-logs', [
            'logs' => $this->scope
                ->sensitiveAccessLogs(SensitiveDataAccessLog::query(), $actor)
                ->with('user', 'subjectUser')
                ->latest('accessed_at')
                ->paginate(25),
        ]);
    }
}
