<?php

namespace App\Http\Controllers\Backoffice\Security;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Models\AuditEvent;
use App\Models\BackupReview;
use App\Models\DataSubjectRequest;
use App\Models\PermissionReview;
use App\Models\SecurityAlert;
use App\Models\SecurityChecklist;
use App\Services\Security\DocumentStorageSecurityReviewService;
use App\Services\Security\SensitiveFieldEncryptionReviewService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SecurityDashboardController extends Controller
{
    public function __invoke(
        Request $request,
        DocumentStorageSecurityReviewService $storageReview,
        SensitiveFieldEncryptionReviewService $fieldReview,
    ): View {
        abort_unless($this->authenticatedUser($request)->hasPermission('settings.view') || $this->authenticatedUser($request)->hasPermission('privacy.view') || $this->authenticatedUser($request)->hasPermission('audit_logs.view'), 403);

        return view('backoffice.security.dashboard', [
            'metrics' => [
                'audit_events' => AuditEvent::query()->count(),
                'access_logs' => AccessLog::query()->count(),
                'open_alerts' => SecurityAlert::query()->whereIn('status', ['open', 'under_review'])->count(),
                'rgpd_requests' => DataSubjectRequest::query()->count(),
                'permission_reviews' => PermissionReview::query()->count(),
                'security_checklists' => SecurityChecklist::query()->count(),
                'backup_reviews' => BackupReview::query()->count(),
            ],
            'storageReview' => $storageReview->review(),
            'fieldReview' => $fieldReview->review(),
            'alerts' => SecurityAlert::query()->latest('detected_at')->limit(8)->get(),
            'rgpdRequests' => DataSubjectRequest::query()->latest('received_at')->limit(8)->get(),
        ]);
    }
}
