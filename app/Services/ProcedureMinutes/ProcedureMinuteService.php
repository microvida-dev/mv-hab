<?php

namespace App\Services\ProcedureMinutes;

use App\Enums\ProcedureMinuteStatus;
use App\Models\Application;
use App\Models\Contest;
use App\Models\ProcedureMinute;
use App\Models\ProcedureTemplate;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\ProcedureMinutes\Renderers\AlcanenaAta01Renderer;
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
        private readonly AlcanenaAta01Renderer $alcanenaAta01Renderer,
        private readonly AuditLogger $auditLogger,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function generate(array $data, User $actor): ProcedureMinute
    {
        abort_unless(
            $actor->hasPermission('documents.generate')
                && $this->municipalScope->hasMunicipalOrGlobalScope($actor),
            403,
        );
        $this->authorizeContext($data, $actor);

        return DB::transaction(function () use ($data, $actor): ProcedureMinute {
            $template = ProcedureTemplate::query()->findOrFail((int) $data['procedure_template_id']);
            $payload = $this->payloadBuilder->build($data, $actor);

            $content = $template->template_number === 'ALC-ATA-01-SERIACAO-INICIAL'
                ? $this->alcanenaAta01Renderer->render($payload)
                : $this->renderer->render(
                    $template,
                    $this->variables->forProcedureMinutePayload($payload, $actor)
                );

            $minute = new ProcedureMinute([
                'title' => $data['title'] ?? 'Ata do procedimento',
                'meeting_date' => $data['meeting_date'] ?? null,
                'subject' => $data['subject'],
                'summary' => 'Ata gerada automaticamente para revisão dos responsáveis competentes.',
            ]);
            $minute->forceFill([
                'minute_number' => $this->number(),
                'contest_id' => data_get($payload, 'contest.id'),
                'program_id' => data_get($payload, 'program.id'),
                'application_id' => data_get($payload, 'application.id'),
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
        abort_unless(
            $actor->hasPermission('documents.approve')
                && $this->municipalScope->ownsProcedureMinute(
                    $actor,
                    $minute,
                ),
            403,
        );

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

    public function delete(ProcedureMinute $minute, User $actor): void
    {
        abort_unless(
            $actor->hasPermission('documents.delete')
                && $this->municipalScope->ownsProcedureMinute(
                    $actor,
                    $minute,
                ),
            403,
        );

        $minute->delete();

        $this->auditLogger->record(
            AuditEvents::DELETE,
            $minute,
            'documents',
            'procedure_minute_delete',
            'Ata do procedimento eliminada.'
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function authorizeContext(array $data, User $actor): void
    {
        $contest = isset($data['contest_id'])
            ? $this->municipalScope
                ->contests(Contest::query(), $actor)
                ->whereKey((int) $data['contest_id'])
                ->firstOrFail()
            : null;
        $application = isset($data['application_id'])
            ? $this->municipalScope
                ->applications(Application::query(), $actor)
                ->whereKey((int) $data['application_id'])
                ->firstOrFail()
            : null;

        abort_if(
            $contest instanceof Contest
                && $application instanceof Application
                && (int) $application->contest_id !== (int) $contest->id,
            422,
        );
        abort_if(
            ! ($contest instanceof Contest)
                && ! ($application instanceof Application),
            422,
        );
    }
}
