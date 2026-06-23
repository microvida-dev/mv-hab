<?php

namespace App\Services\Documents;

use App\Enums\TemplateStatus;
use App\Models\DocumentTemplate;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;

class DocumentTemplateService
{
    public function __construct(
        private readonly DocumentTemplateVersionService $versions,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(array $data, User $actor): DocumentTemplate
    {
        return DB::transaction(function () use ($data, $actor) {
            $template = new DocumentTemplate($data);
            $template->forceFill([
                'status' => TemplateStatus::Draft,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ])->save();
            $this->versions->create($template, $data + ['change_summary' => 'Versão inicial.'], $actor);
            $this->audit->record(AuditEvents::CREATE, $template, 'notifications', 'document_template_created', 'Modelo documental criado.');

            return $template->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(DocumentTemplate $template, array $data, User $actor): DocumentTemplate
    {
        return DB::transaction(function () use ($template, $data, $actor) {
            $template->fill(collect($data)->except(['status'])->all());
            $template->forceFill(['updated_by' => $actor->id])->save();
            $this->versions->create($template, $data + ['change_summary' => 'Alteração do modelo.'], $actor);
            $this->audit->record(AuditEvents::UPDATE, $template, 'notifications', 'document_template_updated', 'Modelo documental atualizado com nova versão.');

            return $template->refresh();
        });
    }

    public function archive(DocumentTemplate $template, User $actor): DocumentTemplate
    {
        $template->forceFill(['status' => TemplateStatus::Archived, 'updated_by' => $actor->id])->save();

        return $template->refresh();
    }
}
