<?php

namespace App\Services\Notifications;

use App\Enums\TemplateStatus;
use App\Models\Contest;
use App\Models\NotificationTemplate;
use App\Models\Program;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;

class NotificationTemplateService
{
    public function __construct(
        private readonly NotificationTemplateVersionService $versions,
        private readonly AuditLogger $audit,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(array $data, User $actor): NotificationTemplate
    {
        abort_unless(
            $actor->hasPermission('notification_templates.create')
                && $this->municipalScope->hasMunicipalOrGlobalScope($actor),
            403,
        );
        $data = $this->normalizeContext($data, $actor);

        return DB::transaction(function () use ($data, $actor) {
            $template = new NotificationTemplate($data);
            $template->forceFill([
                'status' => TemplateStatus::Draft,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ])->save();
            $this->versions->create(
                $template,
                $data + ['change_summary' => 'Versão inicial.'],
                $actor,
                true,
            );
            $this->audit->record(AuditEvents::CREATE, $template, 'notifications', 'notification_template_created', 'Template de comunicação criado.');

            return $template->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(NotificationTemplate $template, array $data, User $actor): NotificationTemplate
    {
        abort_unless(
            $actor->hasPermission('notification_templates.update')
                && $this->municipalScope->canMutateNotificationTemplate(
                    $actor,
                    $template,
                ),
            403,
        );
        $data = $this->normalizeContext($data, $actor, $template);

        return DB::transaction(function () use ($template, $data, $actor) {
            $template->fill(collect($data)->except(['status'])->all());
            $template->forceFill(['updated_by' => $actor->id])->save();
            $this->versions->create(
                $template,
                $data + ['change_summary' => 'Alteração do template.'],
                $actor,
                true,
            );
            $this->audit->record(AuditEvents::UPDATE, $template, 'notifications', 'notification_template_updated', 'Template de comunicação atualizado com nova versão.');

            return $template->refresh();
        });
    }

    public function archive(NotificationTemplate $template, User $actor): NotificationTemplate
    {
        abort_unless(
            $actor->hasPermission('notification_templates.archive')
                && $this->municipalScope->canMutateNotificationTemplate(
                    $actor,
                    $template,
                ),
            403,
        );

        $template->forceFill(['status' => TemplateStatus::Archived, 'updated_by' => $actor->id])->save();
        $this->audit->record(AuditEvents::UPDATE, $template, 'notifications', 'notification_template_archived', 'Template de comunicação arquivado.');

        return $template->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeContext(
        array $data,
        User $actor,
        ?NotificationTemplate $template = null,
    ): array {
        $programId = $this->contextId(
            $data,
            'program_id',
            $template?->program_id,
        );
        $contestId = $this->contextId(
            $data,
            'contest_id',
            $template?->contest_id,
        );
        $municipalityIds = [];

        if ($programId !== null) {
            $program = $this->municipalScope
                ->programs(Program::query(), $actor)
                ->whereKey($programId)
                ->firstOrFail();
            $municipalityIds[] = (int) $program->municipality_id;
        }

        if ($contestId !== null) {
            $contest = $this->municipalScope
                ->contests(Contest::query(), $actor)
                ->whereKey($contestId)
                ->with('program:id,municipality_id')
                ->firstOrFail();
            abort_if(
                $programId !== null
                    && (int) $contest->program_id !== $programId,
                422,
            );
            $municipalityIds[] = (int) $contest->program?->municipality_id;
        }

        if ($actor->municipality_id !== null) {
            $municipalityIds[] = (int) $actor->municipality_id;
        } elseif (
            $municipalityIds === []
            && $template?->municipality_id !== null
        ) {
            $municipalityIds[] = (int) $template->municipality_id;
        }

        $data['municipality_id'] = $this->singleMunicipality(
            $municipalityIds,
        );

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function contextId(
        array $data,
        string $key,
        mixed $fallback,
    ): ?int {
        $value = array_key_exists($key, $data)
            ? $data[$key]
            : $fallback;

        return is_numeric($value) && (int) $value > 0
            ? (int) $value
            : null;
    }

    /**
     * @param  list<int>  $municipalityIds
     */
    private function singleMunicipality(array $municipalityIds): ?int
    {
        $municipalityIds = array_values(array_unique(array_filter(
            $municipalityIds,
            static fn (int $id): bool => $id > 0,
        )));

        abort_if(count($municipalityIds) > 1, 422);

        return $municipalityIds[0] ?? null;
    }
}
