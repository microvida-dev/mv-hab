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

    /**
     * @return array{
     *     month: string,
     *     label: string,
     *     weeks: list<array<string, mixed>>,
     *     summary: array<string, mixed>
     * }
     */
    public function toArray(): array
    {
        return [
            'month' => $this->month->format('Y-m'),
            'label' => $this->month->translatedFormat('F Y'),
            'weeks' => array_values(array_map(
                fn (AgendaWeek $week): array => $week->toArray(),
                $this->weeks
            )),
            'summary' => $this->summary,
        ];
    }
}
