<?php

namespace App\Services\ProcedureTemplates;

use App\Enums\ProcedureTemplateStatus;
use App\Enums\ProcedureTemplateType;
use App\Models\ProcedureTemplate;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;

class ProcedureTemplateService
{
    public function __construct(
        private readonly TemplateRenderingService $renderer,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(array $data, User $actor): ProcedureTemplate
    {
        $content = (string) $data['content'];
        $variables = $data['variables'] ?? $this->renderer->placeholders($content);
        $template = new ProcedureTemplate([
            'type' => ProcedureTemplateType::from((string) $data['type']),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'content' => $content,
            'variables' => $variables,
        ]);
        $template->forceFill([
            'template_number' => $this->number(),
            'status' => ProcedureTemplateStatus::Draft,
            'version' => $data['version'] ?? 1,
            'created_by' => $actor->id,
        ])->save();

        $this->auditLogger->record(AuditEvents::CREATE, $template, 'documents', 'procedure_template_create', 'Minuta de procedimento criada.');

        return $template->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ProcedureTemplate $template, array $data, User $actor): ProcedureTemplate
    {
        if ($template->status === ProcedureTemplateStatus::Active) {
            return DB::transaction(function () use ($template, $data, $actor): ProcedureTemplate {
                $template->forceFill(['status' => ProcedureTemplateStatus::Superseded])->save();
                $new = $this->store([
                    'type' => ($data['type'] ?? $template->type->value),
                    'name' => $data['name'] ?? $template->name,
                    'description' => $data['description'] ?? $template->description,
                    'content' => $data['content'] ?? $template->content,
                    'variables' => $data['variables'] ?? $template->variables,
                    'version' => ((int) $template->version) + 1,
                ], $actor);
                $template->forceFill(['superseded_by' => $new->id])->save();

                return $new;
            });
        }

        $template->fill([
            'type' => ProcedureTemplateType::from((string) ($data['type'] ?? $template->type->value)),
            'name' => $data['name'] ?? $template->name,
            'description' => $data['description'] ?? $template->description,
            'content' => $data['content'] ?? $template->content,
            'variables' => $data['variables'] ?? $template->variables,
        ]);
        $template->forceFill(['updated_by' => $actor->id])->save();

        $this->auditLogger->record(AuditEvents::UPDATE, $template, 'documents', 'procedure_template_update', 'Minuta de procedimento atualizada.');

        return $template->refresh();
    }

    public function publish(ProcedureTemplate $template, User $actor): ProcedureTemplate
    {
        ProcedureTemplate::query()
            ->where('type', $template->type->value)
            ->where('status', ProcedureTemplateStatus::Active->value)
            ->whereKeyNot($template->id)
            ->update(['status' => ProcedureTemplateStatus::Superseded->value]);

        $template->forceFill([
            'status' => ProcedureTemplateStatus::Active,
            'published_by' => $actor->id,
            'published_at' => now(),
        ])->save();

        $this->auditLogger->record(AuditEvents::PUBLISH, $template, 'documents', 'procedure_template_publish', 'Minuta de procedimento publicada.');

        return $template->refresh();
    }

    public function active(ProcedureTemplateType $type): ?ProcedureTemplate
    {
        return ProcedureTemplate::query()
            ->where('type', $type->value)
            ->where('status', ProcedureTemplateStatus::Active->value)
            ->latest('version')
            ->first();
    }

    private function number(): string
    {
        $next = ProcedureTemplate::withTrashed()->count() + 1;

        do {
            $number = 'MIN-'.now()->format('Y').'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (ProcedureTemplate::withTrashed()->where('template_number', $number)->exists());

        return $number;
    }
}
