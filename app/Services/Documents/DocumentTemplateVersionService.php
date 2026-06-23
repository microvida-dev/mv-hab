<?php

namespace App\Services\Documents;

use App\Enums\TemplateStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocumentTemplateVersionService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(DocumentTemplate $template, array $data, User $actor): DocumentTemplateVersion
    {
        $version = new DocumentTemplateVersion([
            'document_template_id' => $template->id,
            'title' => $data['title'] ?? $template->title,
            'body' => $data['body'] ?? $template->body,
            'html_body' => $data['html_body'] ?? $template->html_body,
            'header' => $data['header'] ?? $template->header,
            'footer' => $data['footer'] ?? $template->footer,
            'variables_schema' => $data['variables_schema'] ?? [],
            'change_summary' => $data['change_summary'] ?? null,
        ]);
        $version->forceFill([
            'version_number' => ((int) $template->versions()->max('version_number')) + 1,
            'status' => TemplateStatus::Draft,
            'created_by' => $actor->id,
        ])->save();
        $this->audit->record(AuditEvents::CREATE, $version, 'notifications', 'document_template_version_created', 'Versão de modelo documental criada.');

        return $version;
    }

    public function approve(DocumentTemplateVersion $version, User $actor): DocumentTemplateVersion
    {
        $this->assertUnused($version);
        $version->forceFill(['approved_by' => $actor->id, 'approved_at' => now()])->save();

        return $version->refresh();
    }

    public function activate(DocumentTemplateVersion $version, User $actor): DocumentTemplateVersion
    {
        if (! $version->approved_at) {
            throw ValidationException::withMessages(['version' => 'A versão deve ser aprovada antes de ser ativada.']);
        }

        return DB::transaction(function () use ($version, $actor) {
            $template = $version->template;
            assert($template instanceof DocumentTemplate);

            $template->versions()->whereKeyNot($version->id)->where('status', TemplateStatus::Active->value)->update([
                'status' => TemplateStatus::Archived->value,
                'archived_at' => now(),
            ]);
            $version->forceFill(['status' => TemplateStatus::Active, 'activated_at' => now()])->save();
            $template->forceFill([
                'active_version_id' => $version->id,
                'status' => TemplateStatus::Active,
                'updated_by' => $actor->id,
            ])->save();
            $this->audit->record(AuditEvents::APPROVE, $version, 'notifications', 'document_template_version_activated', 'Versão de modelo documental ativada.');

            return $version->refresh();
        });
    }

    private function assertUnused(DocumentTemplateVersion $version): void
    {
        if ($version->generatedDocuments()->exists()) {
            throw ValidationException::withMessages(['version' => 'Uma versão já usada não pode ser alterada.']);
        }
    }
}
