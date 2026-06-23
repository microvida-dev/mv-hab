<?php

namespace App\Http\Controllers\Backoffice\Reporting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reporting\DownloadReportExportRequest;
use App\Models\ReportExport;
use App\Services\Reporting\ReportDownloadService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportDownloadController extends Controller
{
    public function __construct(private readonly ReportDownloadService $downloads) {}

    public function __invoke(DownloadReportExportRequest $request, ReportExport $reportExport): StreamedResponse
    {
        return $this->downloads->download($reportExport, $this->authenticatedUser($request));
    }
}
