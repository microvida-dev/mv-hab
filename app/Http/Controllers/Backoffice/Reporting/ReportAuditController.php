<?php

namespace App\Http\Controllers\Backoffice\Reporting;

use App\Enums\FeatureKey;
use App\Http\Controllers\Controller;
use App\Models\ReportAccessLog;
use App\Models\ReportDownloadLog;
use App\Services\Entitlements\MunicipalityEntitlementService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class ReportAuditController extends Controller
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly MunicipalityEntitlementService $entitlements,
    ) {}

    public function accessLogs(): View
    {
        Gate::authorize('viewAny', ReportAccessLog::class);

        return view('backoffice.reports.access-logs.index', [
            'logs' => $this->municipalScope
                ->reportAccessLogs(ReportAccessLog::query(), $this->currentUser())
                ->with(['user', 'definition', 'dashboard'])
                ->latest('accessed_at')
                ->paginate(50),
        ]);
    }

    public function downloadLogs(): View
    {
        Gate::authorize('viewAny', ReportAccessLog::class);

        return view('backoffice.reports.download-logs.index', [
            'logs' => $this->municipalScope
                ->reportDownloadLogs(
                    ReportDownloadLog::query(),
                    $this->currentUser(),
                    $this->entitlements->enabledForUser(
                        $this->currentUser(),
                        FeatureKey::ApplicationExport,
                    ),
                )
                ->with(['user', 'export.run.definition'])
                ->latest('downloaded_at')
                ->paginate(50),
        ]);
    }
}
