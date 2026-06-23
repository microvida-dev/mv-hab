<?php

namespace App\Services\Notifications;

use App\Enums\TemplateStatus;
use App\Models\NotificationTemplate;
use App\Models\NotificationTemplateVersion;
use Illuminate\Validation\ValidationException;

class NotificationTemplateResolver
{
    public function resolve(NotificationTemplate $template): NotificationTemplateVersion
    {
        $template->loadMissing('activeVersion');
        $activeVersion = $template->activeVersion;

        if ($template->status !== TemplateStatus::Active || ! ($activeVersion instanceof NotificationTemplateVersion) || $activeVersion->status !== TemplateStatus::Active) {
            throw ValidationException::withMessages(['template' => 'O template não tem uma versão ativa.']);
        }

        return $activeVersion;
    }
}
