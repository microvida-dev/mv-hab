<?php

namespace Database\Factories;

use App\Enums\TemplateStatus;
use App\Models\NotificationTemplate;
use App\Models\NotificationTemplateVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NotificationTemplateVersion> */
class NotificationTemplateVersionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'notification_template_id' => NotificationTemplate::factory(),
            'version_number' => 1,
            'status' => TemplateStatus::Draft,
            'subject' => 'Assunto fictício',
            'title' => 'Comunicação fictícia',
            'body' => 'Conteúdo fictício para teste.',
            'variables_schema' => [],
            'change_summary' => 'Versão de teste.',
        ];
    }
}
