<?php

namespace Database\Factories;

use App\Enums\TimelineEventType;
use App\Enums\TimelineEventVisibility;
use App\Models\Application;
use App\Models\ProcessTimelineEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ProcessTimelineEvent> */
class ProcessTimelineEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_number' => 'TL-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
            'application_id' => Application::factory(),
            'event_type' => TimelineEventType::SystemEvent->value,
            'visibility' => TimelineEventVisibility::CandidateVisible->value,
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'occurred_at' => now(),
        ];
    }
}
