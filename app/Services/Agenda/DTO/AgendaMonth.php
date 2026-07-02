<?php

namespace App\Services\Agenda\DTO;

use Illuminate\Support\Carbon;

final readonly class AgendaMonth
{
    /**
     * @param  array<int, AgendaWeek>  $weeks
     * @param  array<string, mixed>  $summary
     */
    public function __construct(
        public Carbon $month,
        public array $weeks = [],
        public array $summary = [],
    ) {}

    public function toArray(): array
    {
        return [
            'month' => $this->month->format('Y-m'),
            'label' => $this->month->translatedFormat('F Y'),
            'weeks' => array_map(
                fn (AgendaWeek $week): array => $week->toArray(),
                $this->weeks
            ),
            'summary' => $this->summary,
        ];
    }
}
