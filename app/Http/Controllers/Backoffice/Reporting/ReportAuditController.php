<?php

namespace App\Http\Controllers\Backoffice\Reporting;

use App\Http\Controllers\Controller;
use App\Models\ReportAccessLog;
use App\Models\ReportDownloadLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class ReportAuditController extends Controller
{
    public function accessLogs(): View
    {
        Gate::authorize('viewAny', ReportAccessLog::class);

        return view('backoffice.reports.access-logs.index', ['logs' => ReportAccessLog::query()->with(['user', 'definition', 'dashboard'])->latest('accessed_at')->paginate(50)]);
    }

    public function downloadLogs(): View
    {
        Gate::authorize('viewAny', ReportAccessLog::class);

        return view('backoffice.reports.download-logs.index', ['logs' => ReportDownloadLog::query()->with(['user', 'export.run.definition'])->latest('downloaded_at')->paginate(50)]);
    }
}
