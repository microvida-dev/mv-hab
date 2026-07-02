<?php

namespace App\Services\Agenda\DTO;

use Illuminate\Support\Carbon;

final readonly class AgendaWeek
{
    /**
     * @param  array<int, AgendaDay>  $days
     * @param  array<string, mixed>  $summary
     */
    public function __construct(
        public Carbon $start,
        public Carbon $end,
        public array $days = [],
        public array $summary = [],
    ) {}

    public function toArray(): array
    {
        return [
            'start' => $this->start->toDateString(),
            'end' => $this->end->toDateString(),
            'label' => $this->start->format('d/m').' - '.$this->end->format('d/m/Y'),
            'days' => array_map(
                fn (AgendaDay $day): array => $day->toArray(),
                $this->days
            ),
            'summary' => $this->summary,
        ];
    }
}
