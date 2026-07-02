<?php

namespace App\Services\Agenda\DTO;

use App\Data\Dashboard\TimelineEvent;
use Illuminate\Support\Carbon;

final readonly class AgendaDay
{
    /**
     * @param  array<int, TimelineEvent>  $events
     * @param  array<string, mixed>  $statistics
     */
    public function __construct(
        public Carbon $date,
        public array $events = [],
        public array $statistics = [],
    ) {}

    public function toArray(): array
    {
        return [
            'date' => $this->date->toDateString(),
            'label' => $this->date->translatedFormat('d F Y'),
            'events' => array_map(
                fn (TimelineEvent $event): array => $event->toArray(),
                $this->events
            ),
            'statistics' => $this->statistics,
        ];
    }
}
