<?php

namespace App\Services\Lottery;

use App\Models\LotteryDraw;
use App\Models\PostDrawReport;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\Storage;

class PostDrawReportService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function generate(LotteryDraw $draw, User $actor): PostDrawReport
    {
        $draw->loadMissing(['contest', 'participants', 'results.candidate', 'attendances', 'winnerRegistrations']);

        $number = sprintf('RPS-%s-%06d', now()->format('Y'), (PostDrawReport::query()->count() + 1));
        $html = view('backoffice.post-draw-reports.show', [
            'report' => null,
            'lotteryDraw' => $draw,
            'printMode' => true,
        ])->render();

        $path = 'post-draw-reports/'.$number.'.html';
        Storage::disk('local')->put($path, $html);

        $report = new PostDrawReport([
            'lottery_run_id' => $draw->id,
            'contest_id' => $draw->contest_id,
            'title' => 'Relatório pós-sorteio '.$number,
            'summary' => 'Relatório gerado após sorteio auditável.',
            'html_content' => $html,
            'file_disk' => 'local',
            'file_path' => $path,
            'metadata' => [
                'participants_hash' => $draw->participants_hash,
                'result_hash' => $draw->result_hash,
                'method' => $draw->algorithm,
            ],
        ]);

        $report->forceFill([
            'report_number' => $number,
            'status' => 'generated',
            'generated_at' => now(),
            'generated_by' => $actor->id,
        ])->save();

        $this->audit->record(AuditEvents::CREATE, $report, 'reports', 'post_draw_report_generate', 'Relatório pós-sorteio gerado.');

        return $report->refresh();
    }

    public function markDownloaded(PostDrawReport $report, User $actor): PostDrawReport
    {
        $report->forceFill([
            'downloaded_at' => now(),
            'downloaded_by' => $actor->id,
        ])->save();

        $this->audit->record(AuditEvents::ACCESS, $report, 'reports', 'post_draw_report_download', 'Relatório pós-sorteio descarregado.');

        return $report->refresh();
    }
}
