<?php

namespace App\Data\Analytics;

final readonly class WorkloadBucketData
{
    public function __construct(
        public string $name,
        public string $team,
        public int $total,
        public int $overdue,
        public int $dueSoon,
    ) {}

    /**
     * @return array{name: string, team: string, total: int, overdue: int, due_soon: int}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'team' => $this->team,
            'total' => $this->total,
            'overdue' => $this->overdue,
            'due_soon' => $this->dueSoon,
        ];
    }
}
