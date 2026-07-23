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
use App\Services\Security\SecurityMunicipalScopeService;
use App\Services\Security\SensitiveFieldEncryptionReviewService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SecurityDashboardController extends Controller
{
    public function __invoke(
        Request $request,
        DocumentStorageSecurityReviewService $storageReview,
        SensitiveFieldEncryptionReviewService $fieldReview,
        SecurityMunicipalScopeService $scope,
    ): View {
        $actor = $this->authenticatedUser($request);
        abort_unless($actor->hasPermission('security.view'), 403);

        $rgpdRequests = DataSubjectRequest::query()
            ->whereHas('user', fn ($users) => $users->where('municipality_id', $actor->municipality_id));

        return view('backoffice.security.dashboard', [
            'metrics' => [
                'audit_events' => $scope->auditEvents(AuditEvent::query(), $actor)->count(),
                'access_logs' => $scope->accessLogs(AccessLog::query(), $actor)->count(),
                'open_alerts' => $scope
                    ->alerts(SecurityAlert::query(), $actor)
                    ->whereIn('status', ['open', 'under_review'])
                    ->count(),
                'rgpd_requests' => (clone $rgpdRequests)->count(),
                'permission_reviews' => $scope->permissionReviews(PermissionReview::query(), $actor)->count(),
                'security_checklists' => $scope->checklists(SecurityChecklist::query(), $actor)->count(),
                'backup_reviews' => $scope->backupReviews(BackupReview::query(), $actor)->count(),
            ],
            'storageReview' => $storageReview->review(),
            'fieldReview' => $fieldReview->review(),
            'alerts' => $scope
                ->alerts(SecurityAlert::query(), $actor)
                ->latest('detected_at')
                ->limit(8)
                ->get(),
            'rgpdRequests' => $rgpdRequests
                ->latest('received_at')
                ->limit(8)
                ->get(),
        ]);
    }
}
