<?php

namespace App\Services\ProcedureMinutes;

use App\Enums\ProcedureMinuteStatus;
use App\Models\Application;
use App\Models\Contest;
use App\Models\ProcedureMinute;
use App\Models\ProcedureTemplate;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\ProcedureTemplates\TemplateRenderingService;
use App\Services\ProcedureTemplates\TemplateVariableResolver;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;

class ProcedureMinuteService
{
    public function __construct(
        private readonly ProcedureMinutePayloadBuilder $payloadBuilder,
        private readonly ProcedureMinuteExportService $exporter,
        private readonly TemplateVariableResolver $variables,
        private readonly TemplateRenderingService $renderer,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function generate(array $data, User $actor): ProcedureMinute
    {
        return DB::transaction(function () use ($data, $actor): ProcedureMinute {
            $template = ProcedureTemplate::query()->findOrFail((int) $data['procedure_template_id']);
            $application = isset($data['application_id']) ? Application::query()->find($data['application_id']) : null;
            $contest = isset($data['contest_id']) ? Contest::query()->find($data['contest_id']) : $application?->contest;
            $variables = $application instanceof Application
                ? $this->variables->forApplication($application, $actor)
                : ($contest instanceof Contest ? $this->variables->forContest($contest) : ['generated_at' => now()->format('d/m/Y H:i')]);
            $content = $this->renderer->render($template, $variables);
            $payload = $this->payloadBuilder->build($data);

            $minute = new ProcedureMinute([
                'title' => $data['title'] ?? 'Ata do procedimento',
                'meeting_date' => $data['meeting_date'] ?? null,
                'subject' => $data['subject'],
                'summary' => 'Ata gerada automaticamente para revisão dos responsáveis competentes.',
            ]);
            $minute->forceFill([
                'minute_number' => $this->number(),
                'contest_id' => $contest?->id,
                'program_id' => data_get($contest, 'program_id') ?? $application?->program_id,
                'application_id' => $application?->id,
                'procedure_template_id' => $template->id,
                'status' => ProcedureMinuteStatus::Generated,
                'content_snapshot' => $content,
                'payload' => $payload,
                'generated_by' => $actor->id,
                'generated_at' => now(),
            ])->save();

            $minute->forceFill(['file_path' => $this->exporter->export($minute)])->save();
            $this->auditLogger->record(AuditEvents::CREATE, $minute, 'documents', 'procedure_minute_generate', 'Ata do procedimento gerada.');

            return $minute->refresh();
        });
    }

    public function approve(ProcedureMinute $minute, User $actor): ProcedureMinute
    {
        $minute->forceFill([
            'status' => ProcedureMinuteStatus::Approved,
            'approved_by' => $actor->id,
            'approved_at' => now(),
        ])->save();

        $this->auditLogger->record(AuditEvents::APPROVE, $minute, 'documents', 'procedure_minute_approve', 'Ata do procedimento aprovada.');

        return $minute->refresh();
    }

    private function number(): string
    {
        $next = ProcedureMinute::withTrashed()->count() + 1;

        do {
            $number = 'ATA-'.now()->format('Y').'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (ProcedureMinute::withTrashed()->where('minute_number', $number)->exists());

        return $number;
    }
}
