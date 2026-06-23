<?php

namespace Database\Factories;

use App\Enums\CommunicationChannel;
use App\Enums\TemplateStatus;
use App\Enums\TemplateType;
use App\Models\NotificationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NotificationTemplate> */
class NotificationTemplateFactory extends Factory
{
    public function definition(): array
    {
        $code = 'template.'.fake()->unique()->slug(2);

        return [
            'code' => $code,
            'name' => 'Template fictício '.fake()->unique()->numberBetween(100, 9999),
            'description' => 'Conteúdo de teste sem dados pessoais reais.',
            'template_type' => TemplateType::InApp,
            'channel' => CommunicationChannel::InApp,
            'status' => TemplateStatus::Draft,
            'language' => 'pt-PT',
            'subject' => 'Assunto fictício',
            'title' => 'Comunicação fictícia',
            'body' => 'Olá {{ recipient_name }}, esta é uma comunicação fictícia.',
            'requires_acknowledgement' => false,
            'is_official' => false,
            'is_default' => false,
        ];
    }
}
