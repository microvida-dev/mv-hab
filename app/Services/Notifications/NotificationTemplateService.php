<?php

namespace App\Services\Notifications;

use App\Enums\TemplateStatus;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;

class NotificationTemplateService
{
    public function __construct(
        private readonly NotificationTemplateVersionService $versions,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(array $data, User $actor): NotificationTemplate
    {
        return DB::transaction(function () use ($data, $actor) {
            $template = new NotificationTemplate($data);
            $template->forceFill([
                'status' => TemplateStatus::Draft,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ])->save();
            $this->versions->create($template, $data + ['change_summary' => 'Versão inicial.'], $actor);
            $this->audit->record(AuditEvents::CREATE, $template, 'notifications', 'notification_template_created', 'Template de comunicação criado.');

            return $template->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(NotificationTemplate $template, array $data, User $actor): NotificationTemplate
    {
        return DB::transaction(function () use ($template, $data, $actor) {
            $template->fill(collect($data)->except(['status'])->all());
            $template->forceFill(['updated_by' => $actor->id])->save();
            $this->versions->create($template, $data + ['change_summary' => 'Alteração do template.'], $actor);
            $this->audit->record(AuditEvents::UPDATE, $template, 'notifications', 'notification_template_updated', 'Template de comunicação atualizado com nova versão.');

            return $template->refresh();
        });
    }

    public function archive(NotificationTemplate $template, User $actor): NotificationTemplate
    {
        $template->forceFill(['status' => TemplateStatus::Archived, 'updated_by' => $actor->id])->save();
        $this->audit->record(AuditEvents::UPDATE, $template, 'notifications', 'notification_template_archived', 'Template de comunicação arquivado.');

        return $template->refresh();
    }
}
