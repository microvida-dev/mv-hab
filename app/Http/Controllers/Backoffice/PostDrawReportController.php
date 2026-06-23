<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\DownloadPostDrawReportRequest;
use App\Http\Requests\GeneratePostDrawReportRequest;
use App\Models\LotteryDraw;
use App\Models\PostDrawReport;
use App\Services\Lottery\PostDrawReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostDrawReportController extends Controller
{
    public function __construct(private readonly PostDrawReportService $reports) {}

    public function generate(GeneratePostDrawReportRequest $request, LotteryDraw $lotteryDraw): RedirectResponse
    {
        Gate::authorize('create', PostDrawReport::class);

        $report = $this->reports->generate($lotteryDraw, $this->authenticatedUser($request));

        return to_route('backoffice.post-draw-reports.show', $report)->with('success', 'Relatório pós-sorteio gerado.');
    }

    public function show(PostDrawReport $postDrawReport): View
    {
        Gate::authorize('view', $postDrawReport);

        $postDrawReport->load('lotteryDraw.results.candidate');

        return view('backoffice.post-draw-reports.show', [
            'report' => $postDrawReport,
            'lotteryDraw' => $postDrawReport->lotteryDraw,
            'printMode' => false,
        ]);
    }

    public function download(DownloadPostDrawReportRequest $request, PostDrawReport $postDrawReport): StreamedResponse
    {
        Gate::authorize('view', $postDrawReport);

        $report = $this->reports->markDownloaded($postDrawReport, $this->authenticatedUser($request));

        return Storage::disk($report->file_disk ?? 'local')->download(
            (string) $report->file_path,
            $report->report_number.'.html'
        );
    }
}
