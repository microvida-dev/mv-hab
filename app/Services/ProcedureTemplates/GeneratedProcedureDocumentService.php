<?php

namespace App\Services\ProcedureTemplates;

use App\Enums\GeneratedProcedureDocumentStatus;
use App\Enums\ReportFormat;
use App\Models\Application;
use App\Models\Contest;
use App\Models\GeneratedProcedureDocument;
use App\Models\ProcedureTemplate;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GeneratedProcedureDocumentService
{
    public function __construct(
        private readonly TemplateRenderingService $renderer,
        private readonly TemplateVariableResolver $variables,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function generate(ProcedureTemplate $template, array $context, User $actor): GeneratedProcedureDocument
    {
        $application = isset($context['application_id']) ? Application::query()->find($context['application_id']) : null;
        $contest = isset($context['contest_id']) ? Contest::query()->find($context['contest_id']) : $application?->contest;
        $variables = $application instanceof Application
            ? $this->variables->forApplication($application, $actor)
            : ($contest instanceof Contest ? $this->variables->forContest($contest) : ['generated_at' => now()->format('d/m/Y H:i')]);
        $content = $this->renderer->render($template, $variables);

        $document = new GeneratedProcedureDocument([
            'title' => $context['title'] ?? $template->name,
            'format' => ReportFormat::Html,
        ]);
        $related = $application instanceof Model ? $application : $contest;
        $document->forceFill([
            'document_number' => $this->number(),
            'procedure_template_id' => $template->id,
            'type' => $template->type,
            'status' => GeneratedProcedureDocumentStatus::Generated,
            'application_id' => $application?->id,
            'contest_id' => $contest?->id,
            'program_id' => data_get($contest, 'program_id') ?? $application?->program_id,
            'related_type' => $related?->getMorphClass(),
            'related_id' => $related?->getKey(),
            'payload' => ['variables' => $variables, 'source' => $context],
            'content_snapshot' => $content,
            'generated_by' => $actor->id,
            'generated_at' => now(),
        ])->save();

        $path = 'backoffice/generated-documents/'.$document->document_number.'.html';
        Storage::disk('local')->put($path, '<!doctype html><html lang="pt"><meta charset="utf-8"><body>'.$content.'</body></html>');
        $document->forceFill(['file_path' => $path])->save();

        $this->auditLogger->record(AuditEvents::CREATE, $document, 'documents', 'generated_procedure_document_create', 'Documento de procedimento gerado por minuta.');

        return $document->refresh();
    }

    public function approve(GeneratedProcedureDocument $document, User $actor): GeneratedProcedureDocument
    {
        $document->forceFill([
            'status' => GeneratedProcedureDocumentStatus::Approved,
            'approved_by' => $actor->id,
            'approved_at' => now(),
        ])->save();

        $this->auditLogger->record(AuditEvents::APPROVE, $document, 'documents', 'generated_procedure_document_approve', 'Documento de procedimento aprovado.');

        return $document->refresh();
    }

    private function number(): string
    {
        $next = GeneratedProcedureDocument::withTrashed()->count() + 1;

        do {
            $number = 'DOC-PROC-'.now()->format('Y').'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (GeneratedProcedureDocument::withTrashed()->where('document_number', $number)->exists());

        return $number;
    }
}
