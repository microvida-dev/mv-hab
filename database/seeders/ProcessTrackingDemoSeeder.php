<?php

namespace Database\Seeders;

use App\Enums\TimelineEventType;
use App\Enums\TimelineEventVisibility;
use App\Models\Application;
use App\Models\ProcessTimelineEvent;
use Illuminate\Database\Seeder;

class ProcessTrackingDemoSeeder extends Seeder
{
    public function run(): void
    {
        $application = Application::query()->latest()->first();

        if (! $application instanceof Application) {
            return;
        }

        ProcessTimelineEvent::factory()->create([
            'application_id' => $application->id,
            'user_id' => $application->user_id,
            'event_type' => TimelineEventType::ApplicationSubmitted->value,
            'visibility' => TimelineEventVisibility::CandidateVisible->value,
            'title' => 'Candidatura submetida',
            'description' => 'Evento fictício de demonstração do acompanhamento processual.',
        ]);
    }
}
