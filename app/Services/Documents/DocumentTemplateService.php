<?php

namespace App\Services\Documents;

use App\Enums\TemplateStatus;
use App\Models\Contest;
use App\Models\DocumentTemplate;
use App\Models\Program;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocumentTemplateService
{
    public function __construct(
        private readonly DocumentTemplateVersionService $versions,
        private readonly AuditLogger $audit,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(array $data, User $actor): DocumentTemplate
    {
        return DB::transaction(function () use ($data, $actor) {
            $this->assertMunicipalContext($data, $actor);
            $template = new DocumentTemplate($data);
            $template->forceFill([
                'municipality_id' => $actor->municipality_id,
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
            $this->assertMunicipalContext($data, $actor);
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
        $this->audit->record(
            AuditEvents::UPDATE,
            $template,
            'documents',
            'document_template_archived',
            'Modelo documental arquivado.',
        );

        return $template->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertMunicipalContext(array $data, User $actor): void
    {
        $programId = $data['program_id'] ?? null;
        if ($programId !== null) {
            $program = Program::query()->find($programId);
            if (! $program instanceof Program || ! $this->municipalScope->ownsProgram($actor, $program)) {
                throw ValidationException::withMessages([
                    'program_id' => 'O programa indicado não pertence ao âmbito autorizado.',
                ]);
            }
        }

        $contestId = $data['contest_id'] ?? null;
        if ($contestId !== null) {
            $contest = Contest::query()->find($contestId);
            if (! $contest instanceof Contest || ! $this->municipalScope->ownsContest($actor, $contest)) {
                throw ValidationException::withMessages([
                    'contest_id' => 'O concurso indicado não pertence ao âmbito autorizado.',
                ]);
            }
        }
    }
}
