<?php

namespace App\Http\Controllers\Backoffice\Security;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Models\AuditEvent;
use App\Models\SensitiveDataAccessLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function events(Request $request): View
    {
        abort_unless($this->authenticatedUser($request)->hasPermission('audit_logs.view') || $this->authenticatedUser($request)->hasPermission('audit_logs.audit'), 403);

        return view('backoffice.security.audit.events', [
            'events' => AuditEvent::query()->with('user', 'subjectUser')->latest('occurred_at')->paginate(25),
        ]);
    }

    public function event(Request $request, AuditEvent $auditEvent): View
    {
        abort_unless($this->authenticatedUser($request)->hasPermission('audit_logs.view') || $this->authenticatedUser($request)->hasPermission('audit_logs.audit'), 403);

        return view('backoffice.security.audit.event', ['event' => $auditEvent->load('user', 'subjectUser')]);
    }

    public function accessLogs(Request $request): View
    {
        abort_unless($this->authenticatedUser($request)->hasPermission('audit_logs.view') || $this->authenticatedUser($request)->hasPermission('settings.audit'), 403);

        return view('backoffice.security.audit.access-logs', [
            'logs' => AccessLog::query()->with('user')->latest('accessed_at')->paginate(25),
        ]);
    }

    public function sensitiveLogs(Request $request): View
    {
        abort_unless($this->authenticatedUser($request)->hasPermission('audit_logs.view') || $this->authenticatedUser($request)->hasPermission('privacy.audit'), 403);

        return view('backoffice.security.audit.sensitive-logs', [
            'logs' => SensitiveDataAccessLog::query()->with('user', 'subjectUser')->latest('accessed_at')->paginate(25),
        ]);
    }
}
