<?php

namespace App\Services\Notifications;

use App\Enums\TemplateStatus;
use App\Models\NotificationTemplate;
use App\Models\NotificationTemplateVersion;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NotificationTemplateVersionService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(NotificationTemplate $template, array $data, User $actor): NotificationTemplateVersion
    {
        return DB::transaction(function () use ($template, $data, $actor) {
            $version = new NotificationTemplateVersion([
                'notification_template_id' => $template->id,
                'subject' => $data['subject'] ?? $template->subject,
                'title' => $data['title'] ?? $template->title,
                'body' => $data['body'] ?? $template->body,
                'html_body' => $data['html_body'] ?? $template->html_body,
                'sms_body' => $data['sms_body'] ?? $template->sms_body,
                'variables_schema' => $data['variables_schema'] ?? [],
                'change_summary' => $data['change_summary'] ?? null,
            ]);
            $version->forceFill([
                'version_number' => ((int) $template->versions()->max('version_number')) + 1,
                'status' => TemplateStatus::Draft,
                'created_by' => $actor->id,
            ])->save();

            $this->audit->record(AuditEvents::CREATE, $version, 'notifications', 'notification_template_version_created', 'Versão de template criada.');

            return $version;
        });
    }

    public function approve(NotificationTemplateVersion $version, User $actor): NotificationTemplateVersion
    {
        $this->assertUnused($version);
        $version->forceFill([
            'approved_by' => $actor->id,
            'approved_at' => now(),
        ])->save();

        return $version->refresh();
    }

    public function activate(NotificationTemplateVersion $version, User $actor): NotificationTemplateVersion
    {
        if (! $version->approved_at) {
            throw ValidationException::withMessages(['version' => 'A versão deve ser aprovada antes de ser ativada.']);
        }

        return DB::transaction(function () use ($version, $actor) {
            $template = $version->template;
            assert($template instanceof NotificationTemplate);

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
            $this->audit->record(AuditEvents::APPROVE, $version, 'notifications', 'notification_template_version_activated', 'Versão de template ativada.');

            return $version->refresh();
        });
    }

    public function archive(NotificationTemplateVersion $version): NotificationTemplateVersion
    {
        $version->forceFill(['status' => TemplateStatus::Archived, 'archived_at' => now()])->save();

        return $version->refresh();
    }

    private function assertUnused(NotificationTemplateVersion $version): void
    {
        if ($version->communicationLogs()->exists()) {
            throw ValidationException::withMessages(['version' => 'Uma versão já usada não pode ser alterada.']);
        }
    }
}
